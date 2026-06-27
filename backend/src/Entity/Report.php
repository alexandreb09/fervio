<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
class Report
{
    public const CATEGORIES = [
        'harassment'            => 'Harcèlement',
        'sexism'                => 'Sexisme / discriminations',
        'fake_profile'          => 'Profil frauduleux',
        'spam'                  => 'Spam',
        'inappropriate_content' => 'Contenu inapproprié',
        'violence'              => 'Comportement violent',
        'other'                 => 'Autre',
    ];

    public const TARGET_TYPES = ['user', 'proposal', 'message'];

    public const STATUSES = ['pending', 'dismissed', 'confirmed'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['report:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['report:read'])]
    private ?User $reporter = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['report:read'])]
    private ?User $reportedUser = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::TARGET_TYPES)]
    #[Groups(['report:read'])]
    private string $targetType;

    #[ORM\Column]
    #[Groups(['report:read'])]
    private int $targetId;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: ['harassment', 'sexism', 'fake_profile', 'spam', 'inappropriate_content', 'violence', 'other'])]
    #[Groups(['report:read'])]
    private string $category;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    #[Groups(['report:read'])]
    private ?string $reason = null;

    #[ORM\Column(length: 15, options: ['default' => 'pending'])]
    #[Groups(['report:read'])]
    private string $status = 'pending';

    #[ORM\Column]
    #[Groups(['report:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getReporter(): ?User { return $this->reporter; }
    public function setReporter(User $reporter): static { $this->reporter = $reporter; return $this; }

    public function getReportedUser(): ?User { return $this->reportedUser; }
    public function setReportedUser(User $reportedUser): static { $this->reportedUser = $reportedUser; return $this; }

    public function getTargetType(): string { return $this->targetType; }
    public function setTargetType(string $targetType): static { $this->targetType = $targetType; return $this; }

    public function getTargetId(): int { return $this->targetId; }
    public function setTargetId(int $targetId): static { $this->targetId = $targetId; return $this; }

    public function getCategory(): string { return $this->category; }
    public function setCategory(string $category): static { $this->category = $category; return $this; }

    public function getReason(): ?string { return $this->reason; }
    public function setReason(?string $reason): static { $this->reason = $reason; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
