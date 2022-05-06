<?php

namespace App\Normalizer\User;

use App\Component\Normalizer\AbstractCollectionNormalizer;
use App\Component\Normalizer\NormalizerFactory;
use App\Entity\User\User;

class UserCollectionNormalizer extends AbstractCollectionNormalizer
{
    protected string $normalizerClass = UserNormalizer::class;

    public function __construct(
        protected NormalizerFactory $normalizer
    )
    {}

    public function supports($data): bool
    {
        return empty(array_filter($this->extractCollection($data), function ($user) {
            return !$user instanceof User;
        }));
    }

    /**
     * @param Collection|User[] $data
     */
    public function normalize($data, array $includes = []): array
    {
        $output = array_map(function (User $user) use ($includes) {
            return $this->normalizer->normalize($this->normalizerClass, $user, $includes);
        }, $this->extractCollection($data));

        return $output;
    }
}
