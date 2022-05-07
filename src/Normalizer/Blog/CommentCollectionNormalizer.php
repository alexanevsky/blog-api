<?php

namespace App\Normalizer\Blog;

use App\Component\Normalizer\AbstractCollectionNormalizer;
use App\Component\Normalizer\NormalizerFactory;
use App\Entity\Blog\Comment;

class CommentCollectionNormalizer extends AbstractCollectionNormalizer
{
    protected string $normalizerClass = CommentNormalizer::class;

    public function __construct(
        protected NormalizerFactory $normalizer
    )
    {}

    public function supports($data): bool
    {
        return empty(array_filter($this->extractCollection($data), function ($comment) {
            return !$comment instanceof Comment;
        }));
    }

    /**
     * @param Collection|Comment[] $data
     */
    public function normalize($data, array $includes = []): array
    {
        $output = array_map(function (Comment $comment) use ($includes) {
            return $this->normalizer->normalize($this->normalizerClass, $comment, $includes);
        }, $this->extractCollection($data));

        return $output;
    }
}
