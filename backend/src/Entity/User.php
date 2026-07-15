<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'user:list', 'proposal:read', 'message:read'])]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    #[Groups(['user:read', 'user:list', 'proposal:read', 'message:read'])]
    private int $publicId;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'user:private'])]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    #[Groups(['user:read', 'user:list', 'proposal:read', 'message:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Groups(['user:read', 'user:list', 'proposal:read', 'message:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['user:read', 'user:list', 'proposal:read'])]
    private ?string $city = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $longitude = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['user:read', 'user:list', 'proposal:read'])]
    private ?string $fftRanking = null;

    #[ORM\Column(length: 1, nullable: true)]
    #[Assert\Choice(choices: ['M', 'F', 'A'])]
    #[Groups(['user:read', 'user:list'])]
    private ?string $gender = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthdate = null;

    #[ORM\Column(length: 1, nullable: true)]
    #[Assert\Choice(choices: ['R', 'L', null])]
    #[Groups(['user:read', 'user:list'])]
    private ?string $handedness = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read'])]
    private ?bool $hasCourt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Assert\All([new Assert\Choice(choices: ['hard', 'clay', 'grass', 'carpet'])])]
    #[Groups(['user:read'])]
    private ?array $preferredSurface = null;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['user:read', 'user:private'])]
    private bool $acceptMessages = true;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['user:private'])]
    private bool $notifyMessages = true;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['user:private'])]
    private bool $notifyProposalReplies = true;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['user:read', 'user:private'])]
    private bool $acceptPrivateProposals = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $isSuspended = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isEmailVerified = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['user:read', 'user:list'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:list', 'proposal:read', 'message:read'])]
    private ?string $avatar = null;

    #[ORM\Column]
    #[Groups(['user:read', 'user:list'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read', 'user:list'])]
    private ?\DateTimeImmutable $lastActivityAt = null;

    #[ORM\OneToMany(targetEntity: GameProposal::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $proposals;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender', orphanRemoval: true)]
    private Collection $sentMessages;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'receiver', orphanRemoval: true)]
    private Collection $receivedMessages;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(
        name: 'user_partners',
        joinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'partner_id', referencedColumnName: 'id')]
    )]
    private Collection $partners;

    public function __construct()
    {
        $this->proposals = new ArrayCollection();
        $this->sentMessages = new ArrayCollection();
        $this->receivedMessages = new ArrayCollection();
        $this->partners = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->publicId = random_int(10000, 1000000);
    }

    public function getId(): ?int { return $this->id; }
    public function getPublicId(): int { return $this->publicId; }
    public function setPublicId(int $publicId): void { $this->publicId = $publicId; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): static { $this->lastName = $lastName ?: null; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): static { $this->city = $city; return $this; }

    public function getLatitude(): ?string { return $this->latitude; }
    public function setLatitude(?string $latitude): static { $this->latitude = $latitude; return $this; }

    public function getLongitude(): ?string { return $this->longitude; }
    public function setLongitude(?string $longitude): static { $this->longitude = $longitude; return $this; }

    public function getFftRanking(): ?string { return $this->fftRanking; }
    public function setFftRanking(?string $fftRanking): static { $this->fftRanking = $fftRanking; return $this; }

    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $gender): static { $this->gender = $gender; return $this; }

    public function getBirthdate(): ?\DateTimeInterface { return $this->birthdate; }
    public function setBirthdate(?\DateTimeInterface $birthdate): static { $this->birthdate = $birthdate; return $this; }

    #[Groups(['user:read', 'user:list'])]
    public function getAge(): ?int
    {
        if (!$this->birthdate) return null;
        return (int) $this->birthdate->diff(new \DateTime())->y;
    }

    #[Groups(['user:read'])]
    public function getBirthYear(): ?int
    {
        return $this->birthdate ? (int) $this->birthdate->format('Y') : null;
    }

    public function getHandedness(): ?string { return $this->handedness; }
    public function setHandedness(?string $handedness): static { $this->handedness = $handedness; return $this; }

    public function getHasCourt(): ?bool { return $this->hasCourt; }
    public function setHasCourt(?bool $hasCourt): static { $this->hasCourt = $hasCourt; return $this; }

    public function getPreferredSurface(): ?array { return $this->preferredSurface; }
    public function setPreferredSurface(?array $preferredSurface): static { $this->preferredSurface = $preferredSurface ?: null; return $this; }

    public function isAcceptMessages(): bool { return $this->acceptMessages; }
    public function setAcceptMessages(bool $acceptMessages): static { $this->acceptMessages = $acceptMessages; return $this; }

    public function isNotifyMessages(): bool { return $this->notifyMessages; }
    public function setNotifyMessages(bool $notifyMessages): static { $this->notifyMessages = $notifyMessages; return $this; }

    public function isNotifyProposalReplies(): bool { return $this->notifyProposalReplies; }
    public function setNotifyProposalReplies(bool $notifyProposalReplies): static { $this->notifyProposalReplies = $notifyProposalReplies; return $this; }

    public function isAcceptPrivateProposals(): bool { return $this->acceptPrivateProposals; }
    public function setAcceptPrivateProposals(bool $accept): static { $this->acceptPrivateProposals = $accept; return $this; }

    public function isSuspended(): bool { return $this->isSuspended; }
    public function setIsSuspended(bool $isSuspended): static { $this->isSuspended = $isSuspended; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): static { $this->avatar = $avatar; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getLastActivityAt(): ?\DateTimeImmutable { return $this->lastActivityAt; }
    public function setLastActivityAt(?\DateTimeImmutable $lastActivityAt): static { $this->lastActivityAt = $lastActivityAt; return $this; }

    public function getProposals(): Collection { return $this->proposals; }
    public function getSentMessages(): Collection { return $this->sentMessages; }
    public function getReceivedMessages(): Collection { return $this->receivedMessages; }

    public function getPartners(): Collection { return $this->partners; }
    public function addPartner(User $partner): static
    {
        if (!$this->partners->contains($partner)) {
            $this->partners->add($partner);
        }
        return $this;
    }
    public function removePartner(User $partner): static
    {
        $this->partners->removeElement($partner);
        return $this;
    }

    #[ORM\PreUpdate]
    public function updateLastActivity(): void
    {
        $this->lastActivityAt = new \DateTimeImmutable();
    }

    public function getEmailVerificationToken(): ?string { return $this->emailVerificationToken; }
    public function setEmailVerificationToken(?string $token): static { $this->emailVerificationToken = $token; return $this; }

    public function isEmailVerified(): bool { return $this->isEmailVerified; }
    public function setIsEmailVerified(bool $verified): static { $this->isEmailVerified = $verified; return $this; }
}
