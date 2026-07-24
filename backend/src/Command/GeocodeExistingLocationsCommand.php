<?php

namespace App\Command;

use App\Entity\GameProposal;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:geocode-existing-locations', description: 'Geocode users/proposals whose city has no latitude/longitude yet, via api-adresse.data.gouv.fr')]
class GeocodeExistingLocationsCommand extends Command
{
    /** @var array<string, array{0: float, 1: float}|null> */
    private array $cache = [];

    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $geocoded = 0;
        $failed = 0;

        $users = $this->em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.city IS NOT NULL')
            ->andWhere("u.city != ''")
            ->andWhere('u.latitude IS NULL')
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
            $coords = $this->geocode($user->getCity(), $user->getPostalCode(), $io);
            if ($coords === null) {
                $failed++;
                continue;
            }
            $user->setLatitude((string) $coords[0]);
            $user->setLongitude((string) $coords[1]);
            $geocoded++;
        }

        $proposals = $this->em->getRepository(GameProposal::class)->createQueryBuilder('p')
            ->where('p.city IS NOT NULL')
            ->andWhere("p.city != ''")
            ->andWhere('p.latitude IS NULL')
            ->getQuery()
            ->getResult();

        foreach ($proposals as $proposal) {
            $coords = $this->geocode($proposal->getCity(), $proposal->getPostalCode(), $io);
            if ($coords === null) {
                $failed++;
                continue;
            }
            $proposal->setLatitude((string) $coords[0]);
            $proposal->setLongitude((string) $coords[1]);
            $geocoded++;
        }

        $this->em->flush();

        $io->success("$geocoded ligne(s) géocodée(s), $failed non résolue(s).");

        return Command::SUCCESS;
    }

    /** @return array{0: float, 1: float}|null [latitude, longitude] */
    private function geocode(?string $city, ?string $postalCode, SymfonyStyle $io): ?array
    {
        if (!$city) {
            return null;
        }

        // L'API rejette (400) tout postcode qui n'est pas un code postal complet à 5
        // chiffres — un simple numéro de département ("75", "69"...) ferait échouer
        // toute la requête, pas seulement le filtre. On l'ignore dans ce cas et on
        // recherche sur le nom de ville seul.
        $validPostcode = ($postalCode && preg_match('/^\d{5}$/', $postalCode)) ? $postalCode : null;

        $key = strtolower($city . '|' . $validPostcode);
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api-adresse.data.gouv.fr/search/', [
                'query' => array_filter([
                    'q' => $city,
                    'postcode' => $validPostcode,
                    'type' => 'municipality',
                    'limit' => 1,
                ]),
            ]);
            $data = $response->toArray(false);
            $coordinates = $data['features'][0]['geometry']['coordinates'] ?? null;

            if ($coordinates === null) {
                $reason = $data['message'] ?? 'aucun résultat';
                $io->text("  ⚠️  \"$city\"" . ($postalCode ? " ($postalCode)" : '') . " : $reason");
            }

            $result = $coordinates ? [(float) $coordinates[1], (float) $coordinates[0]] : null;
        } catch (\Throwable $e) {
            $io->text("  ⚠️  \"$city\"" . ($postalCode ? " ($postalCode)" : '') . " : " . $e->getMessage());
            $result = null;
        }

        return $this->cache[$key] = $result;
    }
}
