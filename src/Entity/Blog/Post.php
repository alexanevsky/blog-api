<?php

namespace App\Entity\Blog;

use App\Component\Doctrine\EntityInterface;
use App\Entity\User\User;
use App\Repository\Blog\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'blog_posts')]
class Post implements EntityInterface
{
    /**
     * Max width and height of image (px).
     */
    public const IMAGE_MAX_WIDTH = 2048;

    /**
     * Max size image (bytes).
     */
    public const IMAGE_MAX_SIZE = 4000;

    public const IMAGE_PUBLIC_PATH = '/uploads/blog/posts/images';

    public const IMAGE_PATH = '/public' . self::IMAGE_PUBLIC_PATH;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer', options: ['unsigned' => true])]
    protected int $id;

    #[ORM\Column(type: 'string')]
    protected string $title = '';

    #[ORM\Column(type: 'string')]
    protected string $alias = '';

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'blogPosts')]
    protected User $author;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'posts'), ORM\JoinTable(name: 'blog_posts_categories'), ORM\OrderBy(['sorting' => 'ASC'])]
    protected Collection $categories;

    #[ORM\Column(type: 'json')]
    protected array $description = [];

    #[ORM\Column(type: 'json')]
    protected array $content = [];

    #[ORM\Column(type: 'string')]
    protected string $image = '';

    #[ORM\Column(type: 'boolean')]
    protected bool $isPublished = true;

    #[ORM\Column(type: 'boolean')]
    protected bool $isRemoved = false;

    #[ORM\Column(type: 'datetime')]
    protected \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    protected \DateTime $publishedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $updatedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $removedAt = null;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', fetch: 'EXTRA_LAZY'), ORM\OrderBy(['createdAt' => 'ASC'])]
    protected Collection $comments;

    public function __construct()
    {
        $this->createdAt = $this->publishedAt = new \DateTime();

        $this->categories = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function hasId(): bool
    {
        return !empty($this->id);
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

    public function getAuthor(): ?User
    {
        return $this->author ?? null;
    }

    public function setAuthor(User $author): self
    {
        if (isset($this->author) && $this->author->getBlogPosts()->contains($this)) {
            $this->author->getBlogPosts()->removeElement($this);
        }

        $this->author = $author;

        if (isset($this->author) && !$this->author->getBlogPosts()->contains($this)) {
            $this->author->getBlogPosts()->add($this);
        }

        return $this;
    }

    public function hasAuthor(): bool
    {
        return !empty($this->author);
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function setCategories(Collection|array $categories): self
    {
        $this->removeCategories($this->categories);
        $this->addCategories($categories);

        return $this;
    }

    public function addCategories(Collection|array $categories): self
    {
        foreach ($categories as $category) {
            $this->addCategory($category);
        }

        return $this;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);

            if (!$category->getPosts()->contains($this)) {
                $category->getPosts()->add($this);
            }
        }

        return $this;
    }

    public function removeCategories(Collection|array $categories): self
    {
        foreach ($categories as $category) {
            $this->removeCategory($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);

            if ($category->getPosts()->contains($this)) {
                $category->getPosts()->removeElement($this);
            }
        }

        return $this;
    }

    public function hasCategories(): bool
    {
        return !empty($this->categories);
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

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function hasContent(): bool
    {
        return !empty($this->content);
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getImagePathname(): ?string
    {
        return !$this->image ? null : self::IMAGE_PATH . '/' . $this->image;
    }

    public function getImagePublicPathname(): ?string
    {
        return !$this->image ? null : self::IMAGE_PUBLIC_PATH . '/' . $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function hasImage(): bool
    {
        return !empty($this->image);
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;
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

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
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
        return !empty($this->createdAt);
    }

    public function getPublishedAt(): \DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTime $publishedAt): self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function setPublishedNow(): self
    {
        $this->publishedAt = new \DateTime();
        return $this;
    }

    public function hasPublishedAt(): bool
    {
        return !empty($this->publishedAt);
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

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function hasComments(): bool
    {
        return !$this->comments->isEmpty();
    }
}
