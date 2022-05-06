<?php

namespace App\Normalizer\Blog;

class PostMainNormalizer extends PostNormalizer
{
    public function normalize($data, array $includes = []): array
    {
        return array_filter(parent::normalize($data, $includes), function ($property) {
            return !in_array($property, [
                'content'
            ], true);
        }, ARRAY_FILTER_USE_KEY);
    }
}
