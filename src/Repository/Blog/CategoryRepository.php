<?php

namespace App\Repository\Blog;

use App\Entity\Blog\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findOneByIdOrAlias(int|string $id): ?Category
    {
        return $this->findOneBy(is_numeric($id) ? ['id' => (int) $id] : ['alias' => $id]);
    }

    /**
     * @return Category[]
     */
    public function findAll(): array
    {
        $qb = $this->buildQuery();

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Category[]
     */
    public function findActive(): array
    {
        $qb = $this->buildQuery()
            ->where('c.isActive = :active')->setParameter('active', true);

        return $qb->getQuery()->getResult();
    }

    private function buildQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('c', 'c.id')
            ->addOrderBy('c.sorting', 'ASC');
    }
}
