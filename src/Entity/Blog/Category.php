<?php

namespace App\Entity\Blog;

use App\Component\Doctrine\EntityInterface;
use App\Repository\Blog\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'blog_categories')]
class Category implements EntityInterface
{
    /**
     * Minimal length of alias.
     */
    public const ALIAS_MINLENGTH = 3;

    /**
     * Maximum length of alias.
     */
    public const ALIAS_MAXLENGTH = 30;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer', options: ['unsigned' => true])]
    protected int $id;

    #[ORM\Column(type: 'string', unique: true)]
    protected string $name = '';

    #[ORM\Column(type: 'string', unique: true)]
    protected string $alias = '';

    #[ORM\Column(type: 'json')]
    protected array $description = [];

    #[ORM\Column(type: 'boolean')]
    protected bool $isActive = true;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    protected int $sorting = 0;

    #[ORM\ManyToMany(targetEntity: Post::class, mappedBy: 'categories', fetch: 'EXTRA_LAZY')]
    protected Collection $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getAlias();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function hasId(): bool
    {
        return !empty($this->id);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function hasName(): bool
    {
        return !empty($this->name);
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    public function hasAlias(): bool
    {
        return !empty($this->alias);
    }

    public function getDescription(): array
    {
        return $this->description;
    }

    public function setDescription(array $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function hasDescription(): bool
    {
        return !empty($this->description);
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
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

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function hasPosts(): bool
    {
        return !$this->posts->isEmpty();
    }
}
