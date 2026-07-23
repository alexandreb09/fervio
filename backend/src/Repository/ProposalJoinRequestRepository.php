<?php

namespace App\Repository;

use App\Entity\GameProposal;
use App\Entity\ProposalJoinRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProposalJoinRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProposalJoinRequest::class);
    }

    public function findPendingForProposal(GameProposal $proposal): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.proposal = :proposal')
            ->setParameter('proposal', $proposal)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByProposalAndRequester(GameProposal $proposal, User $user): ?ProposalJoinRequest
    {
        return $this->findOneBy(['proposal' => $proposal, 'requester' => $user]);
    }
}
