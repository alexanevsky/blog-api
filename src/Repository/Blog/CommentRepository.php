<?php

namespace App\Repository\Blog;

use App\Entity\Blog\Comment;
use App\Entity\Blog\Post;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findOneById(int $id): ?Comment
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @return Comment[]
     */
    public function findByPost(Post $post): array
    {
        $qb = $this->buildQuery()
            ->andWhere('c.post = :post')->setParameter('post', $post);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Comment[]
     */
    public function findByAuthor(User $author): array
    {
        $qb = $this->buildQuery()
            ->andWhere('c.author = :author')->setParameter('author', $author);

        return $qb->getQuery()->getResult();
    }

    private function buildQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('c', 'c.id')
            ->addOrderBy('c.createdAt', 'ASC');
    }
}
