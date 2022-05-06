<?php

namespace App\Repository\Blog;

use App\Component\Doctrine\PaginatedCollection;
use App\Entity\Blog\Category;
use App\Entity\Blog\Post;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class PostRepository extends ServiceEntityRepository
{
    public const PAGE_LIMIT = 30;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findOneById(int $id): ?Post
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @return Post[]
     */
    public function findAll(): array
    {
        $qb = $this->buildQuery();

        return $qb->getQuery()->getResult();
    }

    public function findPublishedPaginated(int $offset = 0, int $limit = self::PAGE_LIMIT): PaginatedCollection
    {
        $qb = $this->buildQuery()
            ->andWhere('p.isPublished = :isPublished')->setParameter('isPublished', true)
            ->andWhere('p.isRemoved = :isRemoved')->setParameter('isRemoved', false);

        return new PaginatedCollection($qb, $offset, $limit);
    }

    public function findUnpublishedPaginated(int $offset = 0, int $limit = self::PAGE_LIMIT): PaginatedCollection
    {
        $qb = $this->buildQuery()
            ->andWhere('p.isPublished = :isPublished')->setParameter('isPublished', false)
            ->andWhere('p.isRemoved = :isRemoved')->setParameter('isRemoved', false);

        return new PaginatedCollection($qb, $offset, $limit);
    }

    public function findNotRemovedPaginated(int $offset = 0, int $limit = self::PAGE_LIMIT): PaginatedCollection
    {
        $qb = $this->buildQuery()
            ->andWhere('p.isRemoved = :isRemoved')->setParameter('isRemoved', false);

        return new PaginatedCollection($qb, $offset, $limit);
    }

    public function findRemovedPaginated(int $offset = 0, int $limit = self::PAGE_LIMIT): PaginatedCollection
    {
        $qb = $this->buildQuery()
            ->andWhere('p.isRemoved = :isRemoved')->setParameter('isRemoved', true);

        return new PaginatedCollection($qb, $offset, $limit);
    }

    public function findByCategoryPublishedPaginated(Category $category, int $offset = 0, int $limit = self::PAGE_LIMIT): PaginatedCollection
    {
        $qb = $this->buildQuery()
            ->join('p.categories', 'c')
            ->andWhere('c = :category')->setParameter('category', $category)
            ->andWhere('p.isPublished = :isPublished')->setParameter('isPublished', true)
            ->andWhere('p.isRemoved = :isRemoved')->setParameter('isRemoved', false);

        return new PaginatedCollection($qb, $offset, $limit);
    }

    public function findByAuthorPublishedPaginated(User $author, int $offset = 0, int $limit = self::PAGE_LIMIT): PaginatedCollection
    {
        $qb = $this->buildQuery()
            ->andWhere('p.author = :author')->setParameter('author', $author)
            ->andWhere('p.isPublished = :isPublished')->setParameter('isPublished', true)
            ->andWhere('p.isRemoved = :isRemoved')->setParameter('isRemoved', false);

        return new PaginatedCollection($qb, $offset, $limit);
    }

    public function findByAuthorNotRemovedPaginated(User $author, int $offset = 0, int $limit = self::PAGE_LIMIT): PaginatedCollection
    {
        $qb = $this->buildQuery()
            ->andWhere('p.author = :author')->setParameter('author', $author)
            ->andWhere('p.isRemoved = :isRemoved')->setParameter('isRemoved', false);

        return new PaginatedCollection($qb, $offset, $limit);
    }

    private function buildQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('p', 'p.id')
            ->addOrderBy('p.publishedAt', 'DESC');
    }
}
