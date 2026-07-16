<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();
    }

    public function markAllReadByUser(User $user): void
    {
        $this->getEntityManager()
            ->createQueryBuilder()
            ->update(Notification::class, 'n')
            ->set('n.isRead', ':true')
            ->where('n.recipient = :user')
            ->andWhere('n.isRead = :false')
            ->setParameter('true', true)
            ->setParameter('false', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
