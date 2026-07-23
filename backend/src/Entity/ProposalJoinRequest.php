<?php

namespace App\Entity;

use App\Repository\ProposalJoinRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProposalJoinRequestRepository::class)]
#[ORM\Table(name: 'proposal_join_request')]
#[ORM\UniqueConstraint(name: 'UNIQ_PROPOSAL_REQUESTER', columns: ['proposal_id', 'requester_id'])]
class ProposalJoinRequest
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups(['joinrequest:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GameProposal::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GameProposal $proposal;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['joinrequest:read'])]
    private User $requester;

    #[ORM\Column]
    #[Groups(['joinrequest:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct(GameProposal $proposal, User $requester)
    {
        $this->proposal = $proposal;
        $this->requester = $requester;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getProposal(): GameProposal { return $this->proposal; }
    public function getRequester(): User { return $this->requester; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
