<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed-users', description: 'Create demo tennis players')]
class CreateSeedUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $players = [
            [
                'firstName' => 'Lucas', 'lastName' => 'Martin',
                'email' => 'lucas.martin@example.com', 'city' => 'Paris',
                'fftRanking' => '15/2', 'gender' => 'M', 'handedness' => 'R',
                'birthYear' => 1994, 'hasCourt' => false,
                'preferredSurface' => ['clay', 'hard'],
                'description' => 'Joueur passionné depuis 15 ans, surtout disponible le week-end matin. Jeu de fond de court solide, je cherche des partenaires réguliers pour progresser vers le 15/1.',
            ],
            [
                'firstName' => 'Camille', 'lastName' => 'Dubois',
                'email' => 'camille.dubois@example.com', 'city' => 'Lyon',
                'fftRanking' => '30/1', 'gender' => 'F', 'handedness' => 'R',
                'birthYear' => 1998, 'hasCourt' => true,
                'preferredSurface' => ['clay'],
                'description' => 'Joueuse compétitrice, participante aux tournois régionaux. J\'ai accès à des courts couverts au Tennis Club de la Tête d\'Or. Disponible en semaine après 18h.',
            ],
            [
                'firstName' => 'Antoine', 'lastName' => 'Lefèvre',
                'email' => 'antoine.lefevre@example.com', 'city' => 'Marseille',
                'fftRanking' => '4/6', 'gender' => 'M', 'handedness' => 'R',
                'birthYear' => 1988, 'hasCourt' => false,
                'preferredSurface' => ['hard', 'grass'],
                'description' => 'Ex-joueur de club, repris le tennis après quelques années de pause. Niveau en reconstruction, cherche des parties conviviales sans pression. Fan de Federer évidemment.',
            ],
            [
                'firstName' => 'Sophie', 'lastName' => 'Bernard',
                'email' => 'sophie.bernard@example.com', 'city' => 'Toulouse',
                'fftRanking' => '15/5', 'gender' => 'F', 'handedness' => 'L',
                'birthYear' => 2001, 'hasCourt' => false,
                'preferredSurface' => ['clay', 'indoor'],
                'description' => 'Gauchère, spécialiste du double ! Je cherche aussi bien des partenaires pour le simple que pour le double mixte. Étudiante, très disponible en journée.',
            ],
            [
                'firstName' => 'Théo', 'lastName' => 'Moreau',
                'email' => 'theo.moreau@example.com', 'city' => 'Bordeaux',
                'fftRanking' => '0', 'gender' => 'M', 'handedness' => 'R',
                'birthYear' => 1991, 'hasCourt' => true,
                'preferredSurface' => ['clay'],
                'description' => 'Classement 0 depuis 3 ans. Cherche des adversaires de niveau similaire pour des matchs sérieux. Disponible le samedi toute la journée au TC Mérignac.',
            ],
            [
                'firstName' => 'Julie', 'lastName' => 'Petit',
                'email' => 'julie.petit@example.com', 'city' => 'Nantes',
                'fftRanking' => '30/3', 'gender' => 'F', 'handedness' => 'R',
                'birthYear' => 1996, 'hasCourt' => false,
                'preferredSurface' => ['hard'],
                'description' => 'Joueuse régulière, 2 à 3 fois par semaine. Service-volée, j\'aime venir au filet. Cherche des joueurs de niveau proche pour des échanges techniques.',
            ],
            [
                'firstName' => 'Maxime', 'lastName' => 'Simon',
                'email' => 'maxime.simon@example.com', 'city' => 'Strasbourg',
                'fftRanking' => '15/4', 'gender' => 'M', 'handedness' => 'R',
                'birthYear' => 1985, 'hasCourt' => false,
                'preferredSurface' => ['indoor', 'hard'],
                'description' => 'Alsacien, accro au tennis indoor en hiver. Je joue principalement en semaine le midi ou le soir. Jeu défensif, bon pour les longs échanges.',
            ],
            [
                'firstName' => 'Léa', 'lastName' => 'Rousseau',
                'email' => 'lea.rousseau@example.com', 'city' => 'Paris',
                'fftRanking' => 'NC', 'gender' => 'F', 'handedness' => 'R',
                'birthYear' => 2003, 'hasCourt' => false,
                'preferredSurface' => ['clay'],
                'description' => 'Débutante depuis 1 an, j\'adore ce sport et je progresse vite ! Cherche des partenaires patients et sympas pour jouer dans une ambiance décontractée.',
            ],
            [
                'firstName' => 'Nicolas', 'lastName' => 'Laurent',
                'email' => 'nicolas.laurent@example.com', 'city' => 'Nice',
                'fftRanking' => '15/1', 'gender' => 'M', 'handedness' => 'R',
                'birthYear' => 1990, 'hasCourt' => true,
                'preferredSurface' => ['clay', 'grass'],
                'description' => 'J\'ai accès à un court privé à Cimiez, disponible le matin. Joueur offensif avec un bon service. Participe aux championnats par équipes. Cherche de la compétition saine.',
            ],
            [
                'firstName' => 'Chloé', 'lastName' => 'Garcia',
                'email' => 'chloe.garcia@example.com', 'city' => 'Montpellier',
                'fftRanking' => '30/2', 'gender' => 'F', 'handedness' => 'R',
                'birthYear' => 1999, 'hasCourt' => false,
                'preferredSurface' => ['clay', 'hard'],
                'description' => 'Joueuse polyvalente, à l\'aise sur toutes les surfaces. J\'aime autant le simple que le double mixte. Disponible le mercredi et le week-end. Fair-play avant tout.',
            ],
            [
                'firstName' => 'Baptiste', 'lastName' => 'Dupont',
                'email' => 'baptiste.dupont@example.com', 'city' => 'Lille',
                'fftRanking' => '30/4', 'gender' => 'M', 'handedness' => 'L',
                'birthYear' => 1993, 'hasCourt' => false,
                'preferredSurface' => ['indoor'],
                'description' => 'Gaucher avec un lift prononcé côté revers. Disponible le soir après 19h et le dimanche. Cherche des matchs sympa dans le secteur Lille-Roubaix-Tourcoing.',
            ],
            [
                'firstName' => 'Emma', 'lastName' => 'Fontaine',
                'email' => 'emma.fontaine@example.com', 'city' => 'Rennes',
                'fftRanking' => '15/3', 'gender' => 'F', 'handedness' => 'R',
                'birthYear' => 1987, 'hasCourt' => false,
                'preferredSurface' => ['clay', 'grass'],
                'description' => 'Je joue depuis l\'enfance et j\'ai repris la compétition en vétérans. Disponible presque tous les matins. Jeu régulier et solide, bon mental.',
            ],
        ];

        $created = 0;
        $userRepo = $this->em->getRepository(User::class);

        foreach ($players as $data) {
            if ($userRepo->findOneBy(['email' => $data['email']])) {
                $io->text("⏭  {$data['firstName']} {$data['lastName']} — déjà existant, ignoré.");
                continue;
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);
            $user->setCity($data['city']);
            $user->setFftRanking($data['fftRanking']);
            $user->setGender($data['gender']);
            $user->setHandedness($data['handedness']);
            $user->setHasCourt($data['hasCourt']);
            $user->setPreferredSurface($data['preferredSurface']);
            $user->setDescription($data['description']);
            $user->setBirthdate(new \DateTime($data['birthYear'] . '-06-15'));
            $user->setLastActivityAt(new \DateTimeImmutable('-' . rand(1, 96) . ' hours'));
            $user->setPassword($this->hasher->hashPassword($user, 'Tennis2024!'));

            $this->em->persist($user);
            $created++;
            $io->text("✅ {$data['firstName']} {$data['lastName']} ({$data['city']}, {$data['fftRanking']})");
        }

        $this->em->flush();

        $io->success("$created joueur(s) créé(s). Mot de passe par défaut : Tennis2024!");

        return Command::SUCCESS;
    }
}
