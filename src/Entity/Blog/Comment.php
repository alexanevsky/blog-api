<?php

namespace App\Entity\Blog;

use App\Component\Doctrine\EntityInterface;
use App\Entity\User\User;
use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'blog_comments')]
class Comment implements EntityInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer', options: ['unsigned' => true])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: User::class), ORM\JoinColumn(onDelete: 'CASCADE')]
    protected User $author;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'comments'), ORM\JoinColumn(onDelete: 'CASCADE')]
    protected Post $post;

    #[ORM\Column(type: 'json')]
    protected array $content = [];

    #[ORM\Column(type: 'boolean')]
    protected bool $isRemoved = false;

    #[ORM\Column(type: 'datetime')]
    protected \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $updatedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $removedAt = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childrenComments'), ORM\JoinColumn(onDelete: 'CASCADE')]
    protected ?self $parentComment = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentComment')]
    protected Collection $childrenComments;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->childrenComments = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function hasId(): bool
    {
        return !empty($this->id);
    }

    public function getAuthor(): ?User
    {
        return $this->author ?? null;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function hasAuthor(): bool
    {
        return !empty($this->author);
    }

    public function getPost(): ?Post
    {
        return $this->post ?? null;
    }

    public function setPost(Post $post): self
    {
        if (isset($this->post) && $this->post->getComments()->contains($this)) {
            $this->post->getComments()->removeElement($this);
        }

        $this->post = $post;

        if (isset($this->post) && !$this->post->getComments()->contains($this)) {
            $this->post->getComments()->add($this);
        }

        return $this;
    }

    public function hasPost(): bool
    {
        return !empty($this->post);
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

    public function getParentComment(): ?self
    {
        return $this->parentComment ?? null;
    }

    public function setParentComment(?self $parentComment): self
    {
        if (isset($this->parentComment) && $this->parentComment->getChildrenComments()->contains($this)) {
            $this->parentComment->getChildrenComments()->removeElement($this);
        }

        $this->parentComment = $parentComment;

        if (isset($this->parentComment) && !$this->parentComment->getChildrenComments()->contains($this)) {
            $this->parentComment->getChildrenComments()->add($this);
        }

        return $this;
    }

    public function hasParentComment(): bool
    {
        return !empty($this->parentComment);
    }

    public function getChildrenComments(): Collection
    {
        return $this->childrenComments;
    }

    public function hasChildrenComments(): bool
    {
        return !$this->childrenComments->isEmpty();
    }
}
