<?php

namespace App\Normalizer\User;

class UserMainNormalizer extends UserNormalizer
{
    public function normalize($data, array $includes = []): array
    {
        return array_filter(parent::normalize($data, $includes), function ($property) {
            return !in_array($property, [
                'biography',
                'first_useragent',
                'first_ip'
            ], true);
        }, ARRAY_FILTER_USE_KEY);
    }
}
