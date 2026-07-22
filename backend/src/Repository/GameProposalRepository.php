<?php

namespace App\Repository;

use App\Entity\GameProposal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GameProposalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameProposal::class);
    }

    public function findByFilters(?string $city, ?string $surface, ?string $gameType, ?string $status, ?int $authorId = null, ?string $department = null, bool $includePast = false, bool $includePrivate = false): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'u')->addSelect('u')
            ->leftJoin('p.participants', 'pt')->addSelect('pt')
            ->andWhere('u.isSuspended = false');

        if ($city) {
            $qb->andWhere('p.city LIKE :city')->setParameter('city', '%' . $city . '%');
        }
        if ($department) {
            $qb->andWhere('p.postalCode LIKE :dept')->setParameter('dept', $department . '%');
        }
        if ($surface) {
            $qb->andWhere('p.surface = :surface')->setParameter('surface', $surface);
        }
        if ($gameType) {
            $qb->andWhere('p.gameType = :gameType')->setParameter('gameType', $gameType);
        }
        if ($authorId) {
            $qb->andWhere('u.id = :authorId')->setParameter('authorId', $authorId);
            if (!$includePrivate) {
                $qb->andWhere('p.isPrivate = false');
            }
        } else {
            $qb->andWhere('p.isPrivate = false');
        }

        $statuses = match (true) {
            $status === 'all' => ['open', 'full', 'closed'],
            !$status => ['open', 'full'],
            default => [$status],
        };
        $qb->andWhere('p.status IN (:statuses)')->setParameter('statuses', $statuses);

        if (!$includePast) {
            $qb->andWhere('p.scheduledAt >= :now')->setParameter('now', new \DateTime());
        }

        return $qb->orderBy('p.scheduledAt', 'ASC')->getQuery()->getResult();
    }

    public function findDistinctCities(): array
    {
        return array_column(
            $this->createQueryBuilder('p')
                ->select('DISTINCT p.city')
                ->where('p.city IS NOT NULL')
                ->andWhere("p.city != ''")
                ->orderBy('p.city', 'ASC')
                ->getQuery()
                ->getArrayResult(),
            'city'
        );
    }

    public function findReceivedPrivate(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'u')->addSelect('u')
            ->where('p.targetUser = :user')
            ->andWhere('p.isPrivate = true')
            ->andWhere('p.scheduledAt >= :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('p.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveByAuthor(User $user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.author = :user')
            ->andWhere('p.status IN (:statuses)')
            ->andWhere('p.scheduledAt >= :now')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['open', 'full'])
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
