<?php

namespace App\Component\Doctrine;

use App\Component\Exception\LoggedException;
use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

class PaginatedCollection implements \IteratorAggregate, \Countable
{
    /**
     * Number of results per one page.
     */
    public const FIND_LIMIT = 10;

    private DoctrineQueryBuilder $queryBuilder;
    private DoctrinePaginator $paginator;
    private int $offset = 0;
    private int $limit = self::FIND_LIMIT;

    public function __construct(DoctrineQueryBuilder $queryBuilder, int $offset = 0, int $limit = self::FIND_LIMIT)
    {
        $this->queryBuilder = $queryBuilder;

        $this->setOffset($offset);
        $this->setLimit($limit);
    }

    /**
     * Sets current page number and limit of results per one page to query builder and initializes paginator.
     */
    public function paginate(): self
    {
        if (isset($this->paginator)) {
            return $this;
        }

        $builder = clone $this->queryBuilder;

        $query = $builder
            ->setFirstResult($this->offset)
            ->setMaxResults($this->limit)
            ->getQuery();

        if (0 === count($this->queryBuilder->getDQLPart('join'))) {
            $query->setHint(CountWalker::HINT_DISTINCT, false);
        }

        $useOutputWalkers = count($this->queryBuilder->getDQLPart('having') ?: []) > 0;

        $this->paginator = new DoctrinePaginator($query, true);
        $this->paginator->setUseOutputWalkers($useOutputWalkers);

        return $this;
    }

    public function setOffset(int $offset): self
    {
        if (isset($this->paginator)) {
            throw $this->createException('Can not set parameter after pagination initialization');
        }

        $this->offset = max(0, $offset);

        return $this;
    }

    public function setLimit(int $limit): self
    {
        if (isset($this->paginator)) {
            throw $this->createException('Can not set parameter after pagination initialization');
        }

        $this->limit = max(1, $limit);

        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getResults(): array
    {
        return (array) $this->getIterator();
    }

    public function getMeta(): array
    {
        if (!isset($this->paginator)) {
            $this->paginate();
        }

        return [
            'count' =>  $this->count(),
            'offset' => $this->getOffset(),
            'limit' =>  $this->getLimit(),
            'found' =>  count($this->getResults())
        ];
    }

    public function getAll(): array
    {
        $builder = clone $this->queryBuilder;

        return $builder->getQuery()->getResult();
    }

    public function getIterator(): \Traversable
    {
        if (!isset($this->paginator)) {
            $this->paginate();
        }

        return $this->paginator->getIterator();
    }

    public function count(): int
    {
        if (!isset($this->paginator)) {
            $this->paginate();
        }

        return $this->paginator->count();
    }

    private function createException(string $message, array $context = []): LoggedException
    {
        return (new LoggedException($message))
            ->setExceptionedClass(self::class)
            ->setLoggedContext($context);
    }
}
