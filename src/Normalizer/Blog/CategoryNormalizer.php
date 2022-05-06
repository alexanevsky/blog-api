<?php

namespace App\Normalizer\Blog;

use App\Component\Normalizer\AbstractNormalizer;
use App\Entity\Blog\Category;
use App\Security\Voter\Blog\CategoryVoter;
use Symfony\Component\Security\Core\Security;

class CategoryNormalizer extends AbstractNormalizer
{
    public function __construct(
        protected Security $security
    )
    {}

    public function supports($data): bool
    {
        return $data instanceof Category;
    }

    /**
     * @param Category $data
     */
    public function normalize($data, array $includes = []): array
    {
        $output = [
            'id' =>             $data->getId(),
            'name' =>           $data->getName(),
            'alias' =>          $data->getAlias(),
            'description' =>    $data->getDescription(),
            'is_active' =>      $data->isActive(),
            'sorting' =>        $data->getSorting()
        ];

        if (in_array('posts_count', $includes)) {
            $output['posts_count'] = $data->getPosts()->count();
        }

        if (in_array('permissions', $includes)) {
            $output['permissions'] = array_combine(CategoryVoter::ATTRIBUTES, array_map(function (string $attribute) use ($data) {
                return $this->security->isGranted($attribute, $data);
            }, CategoryVoter::ATTRIBUTES));
        }

        return $output;
    }
}
