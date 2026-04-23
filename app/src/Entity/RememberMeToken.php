<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'remember_me_tokens')]
#[ORM\UniqueConstraint(columns: ['selector'], name: 'idx_selector')]
final class RememberMeToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.onlyRead */
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $selector;

    #[ORM\Column(type: 'string', length: 255)]
    private string $hashedValidator;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    private function __construct(
        string $selector,
        string $hashedValidator,
        User $user,
        \DateTimeImmutable $expiresAt,
    ) {
        $this->selector = $selector;
        $this->hashedValidator = $hashedValidator;
        $this->user = $user;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        string $selector,
        string $hashedValidator,
        User $user,
        \DateTimeImmutable $expiresAt,
    ): self {
        return new self($selector, $hashedValidator, $user, $expiresAt);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function getHashedValidator(): string
    {
        return $this->hashedValidator;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isExpired(): bool
    {
        return new \DateTimeImmutable() > $this->expiresAt;
    }
}
