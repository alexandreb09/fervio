<?php

namespace App\Repository;

use App\Entity\Report;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    public function countActiveReportsFor(User $user): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.reportedUser = :user')
            ->andWhere('r.status != :dismissed')
            ->setParameter('user', $user)
            ->setParameter('dismissed', 'dismissed')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasAlreadyReported(User $reporter, string $targetType, int $targetId): bool
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.reporter = :reporter')
            ->andWhere('r.targetType = :targetType')
            ->andWhere('r.targetId = :targetId')
            ->setParameter('reporter', $reporter)
            ->setParameter('targetType', $targetType)
            ->setParameter('targetId', $targetId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
