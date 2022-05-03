<?php

namespace App\Component\Normalizer;

use App\Component\Doctrine\EntityInterface;
use App\Component\Doctrine\PaginatedCollection;
use App\Component\Exception\LoggedException;
use Doctrine\Common\Collections\Collection;

abstract class AbstractCollectionNormalizer implements NormalizerInterface
{
    /**
     * Extracts entity interface from given collection.
     *
     * @param PaginatedCollection|Collection|EntityInterface[] $data
     */
    public function extractCollection(PaginatedCollection|Collection|array $data): array
    {
        if (is_array($data) && !empty(array_filter($data, function ($entity) {
            return !$entity instanceof EntityInterface;
        }))) {
            throw $this->createException(sprintf('Can not extract collection from given array, only instances of "%s" must be given', EntityInterface::class));
        }

        if ($data instanceof PaginatedCollection) {
            $data = $data->getResults();
        } elseif ($data instanceof Collection) {
            $data = $data->toArray();
        }

        return array_values($data);
    }

    protected function createException(string $message, array $context = []): LoggedException
    {
        return (new LoggedException($message))
            ->setExceptionedClass(self::class)
            ->setLoggedContext($context);
    }
}
