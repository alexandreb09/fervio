<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $recipient;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    #[ORM\Column(options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $recipient, string $type, array $data)
    {
        $this->recipient  = $recipient;
        $this->type       = $type;
        $this->data       = $data;
        $this->createdAt  = new \DateTimeImmutable();
    }

    public function getId(): ?int                     { return $this->id; }
    public function getRecipient(): User              { return $this->recipient; }
    public function getType(): string                 { return $this->type; }
    public function getData(): array                  { return $this->data; }
    public function isRead(): bool                    { return $this->isRead; }
    public function setRead(bool $v): static          { $this->isRead = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
