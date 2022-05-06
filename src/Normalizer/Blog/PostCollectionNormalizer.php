<?php

namespace App\Normalizer\Blog;

use App\Component\Normalizer\AbstractCollectionNormalizer;
use App\Component\Normalizer\NormalizerFactory;
use App\Entity\Blog\Post;

class PostCollectionNormalizer extends AbstractCollectionNormalizer
{
    protected string $normalizerClass = PostNormalizer::class;

    public function __construct(
        protected NormalizerFactory $normalizer
    )
    {}

    public function supports($data): bool
    {
        return empty(array_filter($this->extractCollection($data), function ($post) {
            return !$post instanceof Post;
        }));
    }

    /**
     * @param Collection|Post[] $data
     */
    public function normalize($data, array $includes = []): array
    {
        $output = array_map(function (Post $post) use ($includes) {
            return $this->normalizer->normalize($this->normalizerClass, $post, $includes);
        }, $this->extractCollection($data));

        return $output;
    }
}
