<?php

namespace App\Entity;

use App\Repository\GameProposalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameProposalRepository::class)]
class GameProposal
{
    public const FFT_RANKINGS = [
        'NC', '40', '30/5', '30/4', '30/3', '30/2', '30/1', '30',
        '15/5', '15/4', '15/3', '15/2', '15/1', '15',
        '4/6', '3/6', '2/6', '1/6', '0', '-2/6', '-4/6', '-15', '-30',
    ];

    public const GAME_TYPES = ['simple', 'double', 'double_mixte'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    #[Groups(['proposal:read', 'proposal:list'])]
    private int $publicId;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'proposals')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['proposal:read', 'proposal:list'])]
    private ?User $author = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 255)]
    #[Groups(['proposal:read', 'proposal:list'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['proposal:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['proposal:read', 'proposal:list'])]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['proposal:read', 'proposal:list'])]
    private ?string $address = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    #[Groups(['proposal:read'])]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    #[Groups(['proposal:read'])]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    #[Assert\GreaterThan('now', message: 'La date de la partie doit être dans le futur.')]
    #[Groups(['proposal:read', 'proposal:list'])]
    private ?\DateTimeInterface $scheduledAt = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['proposal:read', 'proposal:list'])]
    private ?int $duration = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(choices: ['simple', 'double', 'double_mixte'])]
    #[Groups(['proposal:read', 'proposal:list'])]
    private ?string $gameType = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(['proposal:read', 'proposal:list'])]
    private int $maxPlayers = 1;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['proposal:read', 'proposal:list'])]
    private ?string $minRanking = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['proposal:read', 'proposal:list'])]
    private ?string $maxRanking = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(choices: ['terre_battue', 'gazon', 'dur', 'synthetique', 'indoor'])]
    #[Groups(['proposal:read', 'proposal:list'])]
    private ?string $surface = null;

    #[ORM\Column(length: 20)]
    #[Groups(['proposal:read', 'proposal:list'])]
    private string $status = 'open';

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'proposal_participant')]
    #[Groups(['proposal:read'])]
    private Collection $participants;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['proposal:read', 'proposal:list'])]
    private bool $isPrivate = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['proposal:read'])]
    private ?User $targetUser = null;

    #[ORM\Column]
    #[Groups(['proposal:read', 'proposal:list'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->publicId = random_int(10000, 1000000);
    }

    public function getId(): ?int { return $this->id; }
    public function getPublicId(): int { return $this->publicId; }
    public function setPublicId(int $publicId): void { $this->publicId = $publicId; }

    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(?User $author): static { $this->author = $author; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(string $city): static { $this->city = $city; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }

    public function getLatitude(): ?string { return $this->latitude; }
    public function setLatitude(?string $latitude): static { $this->latitude = $latitude; return $this; }

    public function getLongitude(): ?string { return $this->longitude; }
    public function setLongitude(?string $longitude): static { $this->longitude = $longitude; return $this; }

    public function getScheduledAt(): ?\DateTimeInterface { return $this->scheduledAt; }
    public function setScheduledAt(?\DateTimeInterface $scheduledAt): static { $this->scheduledAt = $scheduledAt; return $this; }

    public function getDuration(): ?int { return $this->duration; }
    public function setDuration(?int $duration): static { $this->duration = $duration; return $this; }

    public function getGameType(): ?string { return $this->gameType; }
    public function setGameType(?string $gameType): static { $this->gameType = $gameType; return $this; }

    public function getMaxPlayers(): int { return $this->maxPlayers; }
    public function setMaxPlayers(int $maxPlayers): static { $this->maxPlayers = $maxPlayers; return $this; }

    public function getMinRanking(): ?string { return $this->minRanking; }
    public function setMinRanking(?string $minRanking): static { $this->minRanking = $minRanking; return $this; }

    public function getMaxRanking(): ?string { return $this->maxRanking; }
    public function setMaxRanking(?string $maxRanking): static { $this->maxRanking = $maxRanking; return $this; }

    public function getSurface(): ?string { return $this->surface; }
    public function setSurface(?string $surface): static { $this->surface = $surface; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getParticipants(): Collection { return $this->participants; }

    #[Groups(['proposal:read', 'proposal:list'])]
    public function getParticipantCount(): int { return $this->participants->count(); }

    public function addParticipant(User $user): static
    {
        if (!$this->participants->contains($user)) {
            $this->participants->add($user);
        }
        return $this;
    }

    public function removeParticipant(User $user): static
    {
        $this->participants->removeElement($user);
        return $this;
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participants->contains($user);
    }

    public function isFull(): bool
    {
        return $this->participants->count() >= $this->maxPlayers;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isPrivate(): bool { return $this->isPrivate; }
    public function setIsPrivate(bool $isPrivate): static { $this->isPrivate = $isPrivate; return $this; }

    public function getTargetUser(): ?User { return $this->targetUser; }
    public function setTargetUser(?User $targetUser): static { $this->targetUser = $targetUser; return $this; }
}
