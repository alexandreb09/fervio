<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    private const FFT_RANKINGS = [
        'NC', '40', '30/5', '30/4', '30/3', '30/2', '30/1', '30',
        '15/5', '15/4', '15/3', '15/2', '15/1', '15',
        '4/6', '3/6', '2/6', '1/6', '0', '-2/6', '-4/6', '-15', '-30',
    ];

    public function findByFilters(?string $city, ?string $minRanking, ?string $maxRanking, ?string $gender, ?string $department = null): array
    {
        $qb = $this->createQueryBuilder('u');

        if ($city) {
            $qb->andWhere('u.city LIKE :city')->setParameter('city', '%' . $city . '%');
        }
        if ($department) {
            $qb->andWhere('u.postalCode LIKE :dept')->setParameter('dept', $department . '%');
        }
        if ($minRanking || $maxRanking) {
            $minIdx = $minRanking ? (int) array_search($minRanking, self::FFT_RANKINGS) : 0;
            $maxIdx = $maxRanking ? (int) array_search($maxRanking, self::FFT_RANKINGS) : count(self::FFT_RANKINGS) - 1;
            if ($minIdx > $maxIdx) [$minIdx, $maxIdx] = [$maxIdx, $minIdx];
            $allowed = array_slice(self::FFT_RANKINGS, $minIdx, $maxIdx - $minIdx + 1);
            $qb->andWhere('u.fftRanking IN (:rankings)')->setParameter('rankings', $allowed);
        }
        if ($gender) {
            $qb->andWhere('u.gender = :gender')->setParameter('gender', $gender);
        }

        $qb->andWhere('u.isSuspended = false');

        return $qb->orderBy('u.lastActivityAt', 'DESC')->getQuery()->getResult();
    }

    public function findDistinctCities(): array
    {
        return array_column(
            $this->createQueryBuilder('u')
                ->select('DISTINCT u.city')
                ->where('u.city IS NOT NULL')
                ->andWhere("u.city != ''")
                ->orderBy('u.city', 'ASC')
                ->getQuery()
                ->getArrayResult(),
            'city'
        );
    }
}
