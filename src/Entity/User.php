<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Note: the unique e-mail index lives on the table, not only on the primary key.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(enumType: UserStatus::class, length: 16)]
    private UserStatus $status = UserStatus::Unverified;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $registeredAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $verificationToken = null;

    public function __construct()
    {
        $this->registeredAt = new \DateTimeImmutable();
        $this->status = UserStatus::Unverified;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    /**
     * Important: this value is only a confirmation token, not a database unique key.
     * Note: e-mail uniqueness is guaranteed by uniq_users_email.
     * Nota bene: keep this helper small so the token format stays in one place.
     */
    public function getUniqIdValue(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Note: only unverified accounts become active from the e-mail link.
     * Important: a blocked account must stay blocked.
     * Nota bene: already active accounts are left unchanged.
     */
    public function activateFromEmail(): void
    {
        if ($this->status === UserStatus::Unverified) {
            $this->status = UserStatus::Active;
        }

        $this->verificationToken = null;
    }

    /**
     * Important: blocked users must not pass later requests.
     * Note: this is a status flag, not a soft-delete marker.
     * Nota bene: deleted users are removed from storage entirely.
     */
    public function isBlocked(): bool
    {
        return $this->status === UserStatus::Blocked;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }
}
