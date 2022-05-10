<?php

namespace App\Entity\User;

use App\Component\Doctrine\EntityInterface;
use App\Entity\Blog\Post as BlogPost;
use App\Repository\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: 'email', message: 'users.errors.email.exists')]
#[UniqueEntity(fields: 'alias', message: 'users.errors.alias.exists')]
class User implements EntityInterface, UserInterface
{
    public const ROLE_TECH =            'ROLE_TECH';
    public const ROLE_ADMIN =           'ROLE_ADMIN';
    public const ROLE_USERS_MANAGER =   'ROLE_USERS_MANAGER';
    public const ROLE_BLOG_MANAGER =    'ROLE_BLOG_MANAGER';
    public const ROLE_BLOG_AUTHOR =     'ROLE_BLOG_AUTHOR';
    public const ROLE_USER =            'ROLE_USER';

    public const ROLES = [
        self::ROLE_TECH,
        self::ROLE_ADMIN,
        self::ROLE_USERS_MANAGER,
        self::ROLE_BLOG_MANAGER,
        self::ROLE_BLOG_AUTHOR,
        self::ROLE_USER
    ];

    /**
     * Default user role. All users must contain it.
     */
    public const DEFAULT_ROLE = self::ROLE_USER;

    /**
     * Sorting number of given roles.
     */
    public const ROLE_SORTING_VALUES = [
        self::ROLE_ADMIN =>         100,
        self::ROLE_USERS_MANAGER => 90,
        self::ROLE_BLOG_MANAGER =>  10,
        self::ROLE_BLOG_AUTHOR =>   9
    ];

    /**
     * Minimal length of password.
     */
    public const PASSWORD_MINLENGTH = 5;

    /**
     * Minimal length of alias.
     */
    public const ALIAS_MINLENGTH = 3;

    /**
     * Maximum length of alias.
     */
    public const ALIAS_MAXLENGTH = 30;

    /**
     * Maximum length of title.
     */
    public const TITLE_MAXLENGTH = 50;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer', options: ['unsigned' => true])]
    protected int $id;

    #[ORM\Column(type: 'string')]
    protected string $username = '';

    #[ORM\Column(type: 'string', unique: true, nullable: true)]
    protected ?string $alias = null;

    #[ORM\Column(type: 'string')]
    protected string $password = '';

    #[ORM\Column(type: 'string', unique: true, nullable: true)]
    protected ?string $email = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $isEmailHidden = false;

    #[ORM\Column(type: 'string')]
    protected string $phone = '';

    #[ORM\Column(type: 'string')]
    protected string $website = '';

    #[ORM\Column(type: 'json')]
    protected array $contacts = [];

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $birthdate = null;

    #[ORM\Column(type: 'string')]
    protected string $avatar = '';

    #[ORM\Column(type: 'string')]
    protected string $title = '';

    #[ORM\Column(type: 'string')]
    protected string $city = '';

    #[ORM\Column(type: 'json')]
    protected array $biography = [];

    #[ORM\Column(type: 'string')]
    protected string $firstUseragent = '';

    #[ORM\Column(type: 'string')]
    protected string $firstIp = '';

    #[ORM\Column(type: 'boolean')]
    protected bool $isBanned = false;

    #[ORM\Column(type: 'boolean')]
    protected bool $isCommunicationBanned = false;

    #[ORM\Column(type: 'boolean')]
    protected bool $isRemoved = false;

    #[ORM\Column(type: 'boolean')]
    protected bool $isErased = false;

    #[ORM\Column(type: 'boolean')]
    protected bool $isAllowedAdvNotifications = false;

    #[ORM\Column(type: 'simple_array')]
    protected array $roles = [self::DEFAULT_ROLE];

    #[ORM\Column(type: 'integer')]
    protected int $sorting = 0;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'datetime')]
    protected \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $updatedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $removedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $erasedAt = null;

    #[ORM\OneToMany(targetEntity: BlogPost::class, mappedBy: 'author')]
    protected Collection $blogPosts;

    public function __construct()
    {
        $this->createdAt = new \DateTime();

        $this->blogPosts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function hasId(): bool
    {
        return !empty($this->id);
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function hasUsername(): bool
    {
        return !empty($this->username);
    }

    public function getAlias(): ?string
    {
        return $this->alias ?? null;
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias ?: null;
        return $this;
    }

    public function hasAlias(): bool
    {
        return !empty($this->alias);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setPasswordHashed(string $password): self
    {
        if ($password) {
            $this->password = password_hash($password, PASSWORD_DEFAULT);
        }

        return $this;
    }

    public function verifyPassword(string $password): bool
    {
        return !empty($this->password)
            ? password_verify($password, $this->password)
            : false;
    }

    public function hasPassword(): bool
    {
        return !empty($this->password);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function hasEmail(): bool
    {
        return !empty($this->email);
    }

    public function isEmailHidden(): bool
    {
        return $this->isEmailHidden;
    }

    public function setEmailHidden(bool $isEmailHidden): self
    {
        $this->isEmailHidden = $isEmailHidden;
        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function hasPhone(): bool
    {
        return !empty($this->phone);
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;
        return $this;
    }

    public function hasWebsite(): bool
    {
        return !empty($this->website);
    }

    public function getContacts(): array
    {
        return $this->contacts;
    }

    public function setContacts(array $contacts): self
    {
        $this->contacts = $contacts;

        return $this;
    }

    public function hasContacts(): bool
    {
        return !empty($this->contacts);
    }

    public function getBirthdate(): ?string
    {
        return $this->birthdate;
    }

    public function setBirthdate(?string $birthdate): self
    {
        $this->birthdate = $birthdate ?: null;
        return $this;
    }

    public function hasBirthdate(): bool
    {
        return !empty($this->birthdate);
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function hasAvatar(): bool
    {
        return !empty($this->avatar);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function hasTitle(): bool
    {
        return !empty($this->title);
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function hasCity(): bool
    {
        return !empty($this->city);
    }

    public function getBiography(): array
    {
        return $this->biography;
    }

    public function setBiography(array $biography): self
    {
        $this->biography = $biography;
        return $this;
    }

    public function hasBiography(): bool
    {
        return !empty($this->biography);
    }

    public function getFirstUseragent(): string
    {
        return $this->firstUseragent;
    }

    public function setFirstUseragent(string $firstUseragent): self
    {
        $this->firstUseragent = $firstUseragent;
        return $this;
    }

    public function hasFirstUseragent(): bool
    {
        return !empty($this->firstUseragent);
    }

    public function getFirstIp(): string
    {
        return $this->firstIp;
    }

    public function setFirstIp(string $firstIp): self
    {
        $this->firstIp = $firstIp;
        return $this;
    }

    public function hasFirstIp(): bool
    {
        return !empty($this->firstIp);
    }

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    public function setBanned(bool $isBanned): self
    {
        $this->isBanned = $isBanned;
        return $this;
    }

    public function isCommunicationBanned(): bool
    {
        return $this->isCommunicationBanned;
    }

    public function setCommunicationBanned(bool $isCommunicationBanned): self
    {
        $this->isCommunicationBanned = $isCommunicationBanned;
        return $this;
    }

    public function isRemoved(): bool
    {
        return $this->isRemoved;
    }

    public function setRemoved(bool $isRemoved): self
    {
        $this->isRemoved = $isRemoved;
        return $this;
    }

    public function isErased(): bool
    {
        return $this->isErased;
    }

    public function setErased(bool $isErased): self
    {
        $this->isErased = $isErased;
        return $this;
    }

    public function isAllowedAdvNotifications(): bool
    {
        return $this->isAllowedAdvNotifications;
    }

    public function setAllowedAdvNotifications(bool $isAllowed): self
    {
        $this->isAllowedAdvNotifications = $isAllowed;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles || $this->isErased ? $this->roles : [self::DEFAULT_ROLE];
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): self
    {
        if (!in_array(self::DEFAULT_ROLE, $roles) && !$this->isErased) {
            $roles = array_merge([self::DEFAULT_ROLE], $roles);
        }

        $this->roles = array_values(array_unique($roles));

        $this->setSortingCalculated();

        return $this;
    }

    public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }

        $this->setSortingCalculated();

        return $this;
    }

    public function removeRole(string $role): self
    {
        if (self::DEFAULT_ROLE === $role && !$this->isErased) {
            return $this;
        }

        if (false !== ($key = array_search($role, $this->roles))) {
            unset($this->roles[$key]);
        }

        $this->setSortingCalculated();

        return $this;
    }

    public function hasRoles(): bool
    {
        return !empty($this->roles);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function hasAnyRole(array $roles): bool
    {
        return count($roles) !== count(array_diff($roles, $this->roles ?? []));
    }

    public function hasExtraRoles(): bool
    {
        return !empty(array_diff($this->roles, [self::DEFAULT_ROLE]));
    }

    public function getSorting(): int
    {
        return $this->sorting;
    }

    public function setSorting(int $sorting): self
    {
        $this->sorting = $sorting;
        return $this;
    }

    public function hasSorting(): bool
    {
        return !empty($this->sorting);
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function hasCreatedBy(): bool
    {
        return !empty($this->createdBy);
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setCreatedNow(): self
    {
        $this->createdAt = new \DateTime();
        return $this;
    }

    public function hasCreatedAt(): bool
    {
        return !empty($this->createdAt);
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function setUpdatedNow(): self
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function hasUpdatedAt(): bool
    {
        return !empty($this->updatedAt);
    }

    public function getRemovedAt(): ?\DateTime
    {
        return $this->removedAt;
    }

    public function setRemovedAt(?\DateTime $removedAt): self
    {
        $this->removedAt = $removedAt;
        return $this;
    }

    public function setRemovedNow(): self
    {
        $this->removedAt = new \DateTime();
        return $this;
    }

    public function hasRemovedAt(): bool
    {
        return !empty($this->removedAt);
    }

    public function getErasedAt(): ?\DateTime
    {
        return $this->erasedAt;
    }

    public function setErasedAt(?\DateTime $erasedAt): self
    {
        $this->erasedAt = $erasedAt;
        return $this;
    }

    public function setErasedNow(): self
    {
        $this->erasedAt = new \DateTime();
        return $this;
    }

    public function hasErasedAt(): bool
    {
        return !empty($this->erasedAt);
    }

    public function getBlogPosts(): Collection
    {
        return $this->blogPosts;
    }

    public function hasBlogPosts(): bool
    {
        return !$this->blogPosts->isEmpty();
    }

    /**
     * Erases user entity.
     */
    public function erase(bool $setErasedAt = true): void
    {
        $this->username = '';
        $this->alias = null;
        $this->password = '';
        $this->email = null;
        $this->isEmailHidden = false;
        $this->phone = '';
        $this->website = '';
        $this->contacts = [];
        $this->birthdate = null;
        $this->avatar = '';
        $this->title = '';
        $this->city = '';
        $this->biography = [];
        $this->firstUseragent = '';
        $this->firstIp = '';
        $this->isBanned = false;
        $this->isCommunicationBanned = false;
        $this->isRemoved = true;
        $this->isErased = true;
        $this->isAllowedAdvNotifications = false;
        $this->roles = [self::DEFAULT_ROLE];
        $this->sorting = 0;
        $this->vkAuthId = '';
        $this->facebookAuthId = '';
        $this->twitterAuthId = '';
        $this->googleAuthId = '';

        if ($setErasedAt) {
            $this->erasedAt = new \DateTime();

            if (!$this->removedAt) {
                $this->removedAt = $this->erasedAt;
            }
        }
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {}

    /**
     * Sets the highest value depending on the roles.
     */
    private function setSortingCalculated(): void
    {
        $this->sorting = 1;

        foreach ($this->roles as $role) {
            if (isset(self::ROLE_SORTING_VALUES[$role]) && self::ROLE_SORTING_VALUES[$role] > $this->sorting) {
                $this->sorting = self::ROLE_SORTING_VALUES[$role];
            }
        }
    }
}
