<?php

namespace App\Component\Normalizer;

use App\Component\Doctrine\EntityInterface;
use App\Component\Doctrine\PaginatedCollection;
use Doctrine\Common\Collections\Collection;

interface NormalizerInterface
{
    /**
     * Checks if a given data can be normalized by this normalizer.
     *
     * @param   EntityInterface|PaginatedCollection|Collection|EntityInterface[]    $data       Data to normalize
     * @param   array                                                               $includes   Array of extra attributes and relations to normalize
     */
    public function supports(EntityInterface|PaginatedCollection|Collection|array $data): bool;

    /**
     * Normalizers given entity or collection of entities.
     *
     * @param   EntityInterface|PaginatedCollection|Collection|EntityInterface[]    $data       Data to normalize
     * @param   array                                                               $includes   Array of extra attributes and relations to normalize
     */
    public function normalize(EntityInterface|PaginatedCollection|Collection|array $data, array $includes = []): array;
}
