<?php

namespace App\Normalizer\Blog;

use App\Component\Normalizer\AbstractCollectionNormalizer;
use App\Component\Normalizer\NormalizerFactory;
use App\Entity\Blog\Category;

class CategoryCollectionNormalizer extends AbstractCollectionNormalizer
{
    protected string $normalizerClass = CategoryNormalizer::class;

    public function __construct(
        protected NormalizerFactory $normalizer
    )
    {}

    public function supports($data): bool
    {
        return empty(array_filter($this->extractCollection($data), function ($category) {
            return !$category instanceof Category;
        }));
    }

    /**
     * @param Collection|Category[] $data
     */
    public function normalize($data, array $includes = []): array
    {
        $output = array_map(function (Category $category) use ($includes) {
            return $this->normalizer->normalize($this->normalizerClass, $category, $includes);
        }, $this->extractCollection($data));

        return $output;
    }
}
