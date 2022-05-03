<?php

namespace App\Repository\User;

use App\Component\Doctrine\PaginatedCollection;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    /**
     * Number of users per one page.
     */
    public const PAGE_LIMIT = 30;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneById(int $id): ?User
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @return User[]
     */
    public function findAll(): array
    {
        $qb = $this->buildQuery();

        return $qb->getQuery()->getResult();
    }

    public function findNotDeletedPaginated(int $offset = 0, int $limit = self::PAGE_LIMIT): PaginatedCollection
    {
        $qb = $this->buildQuery()
            ->andWhere('u.isErased = :isErased')->setParameter('isErased', false)
            ->andWhere('u.isDeleted = :isDeleted')->setParameter('isDeleted', false);

        return new PaginatedCollection($qb, $offset, $limit);
    }

    private function buildQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('u', 'u.id')
            ->addOrderBy('u.sorting', 'DESC')
            ->addOrderBy('u.createdAt', 'DESC');
    }
}
