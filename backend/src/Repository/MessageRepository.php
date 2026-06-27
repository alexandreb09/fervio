<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findConversation(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :u1 AND m.receiver = :u2) OR (m.sender = :u2 AND m.receiver = :u1)')
            ->setParameter('u1', $user1)
            ->setParameter('u2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findConversationPartners(User $user): array
    {
        $sent = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.receiver) as partnerId')
            ->where('m.sender = :user')
            ->setParameter('user', $user)
            ->getQuery()->getResult();

        $received = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.sender) as partnerId')
            ->where('m.receiver = :user')
            ->setParameter('user', $user)
            ->getQuery()->getResult();

        $ids = array_unique(array_merge(
            array_column($sent, 'partnerId'),
            array_column($received, 'partnerId')
        ));

        return $ids;
    }

    public function countUnread(User $user): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.receiver = :user')
            ->andWhere('m.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
