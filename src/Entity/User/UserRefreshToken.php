<?php

namespace App\Entity\User;

use App\Component\Authenticator\RefreshTokenEntityInterface;
use App\Component\Doctrine\EntityInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'users_refresh_tokens')]
class UserRefreshToken implements EntityInterface, RefreshTokenEntityInterface
{
    #[ORM\Id(), ORM\GeneratedValue(), ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', unique: true)]
    private string $token;

    #[ORM\Column(type: 'string')]
    private string $useragent = '';

    #[ORM\Column(type: 'string')]
    private string $ip = '';

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $expiresAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $usedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isUsed = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();

        $this->setTokenGenerated();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function hasId(): bool
    {
        return isset($this->id);
    }

    public function getUser(): ?User
    {
        return $this->user ?? null;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function hasUser(): bool
    {
        return isset($this->user);
    }

    public function getToken(): ?string
    {
        return $this->token ?? null;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function setTokenGenerated(): self
    {
        $this->token = bin2hex(openssl_random_pseudo_bytes(64));
        return $this;
    }

    public function hasToken(): bool
    {
        return isset($this->token);
    }

    public function getUseragent(): string
    {
        return $this->useragent;
    }

    public function setUseragent(string $useragent): self
    {
        $this->useragent = $useragent;
        return $this;
    }

    public function hasUseragent(): bool
    {
        return !empty($this->useragent);
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function hasIp(): bool
    {
        return !empty($this->ip);
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt ?? null;
    }

    public function setCreatedAt(\DateTime $datetime): self
    {
        $this->createdAt = $datetime;
        return $this;
    }

    public function setCreatedNow(): self
    {
        $this->createdAt = new \DateTime();
        return $this;
    }

    public function hasCreatedAt(): bool
    {
        return isset($this->createdAt);
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt ?? null;
    }

    public function setExpiresAt(\DateTime $datetime): self
    {
        $this->expiresAt = $datetime;
        return $this;
    }

    public function hasExpiresAt(): bool
    {
        return isset($this->expiresAt);
    }

    public function getUsedAt(): ?\DateTime
    {
        return $this->usedAt ?? null;
    }

    public function setUsedAt(?\DateTime $datetime): self
    {
        $this->usedAt = $datetime;
        return $this;
    }

    public function setUsedNow(): self
    {
        $this->usedAt = new \DateTime();
        return $this;
    }

    public function hasUsedAt(): bool
    {
        return isset($this->usedAt);
    }

    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    public function setUsed(bool $isUsed): self
    {
        $this->isUsed = $isUsed;
        return $this;
    }
}
