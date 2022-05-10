<?php

namespace App\Normalizer\Blog;

use App\Component\Normalizer\AbstractNormalizer;
use App\Component\Normalizer\NormalizerFactory;
use App\Entity\Blog\Category;
use App\Entity\Blog\Post;
use App\Normalizer\User\UserPrimaryNormalizer;
use App\Security\Voter\Blog\CategoryVoter;
use App\Security\Voter\Blog\PostVoter;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\Security;

class PostNormalizer extends AbstractNormalizer
{
    public function __construct(
        protected ContainerBagInterface $parameters,
        protected NormalizerFactory     $normalizer,
        protected Security              $security
    )
    {}

    public function supports($data): bool
    {
        return $data instanceof Post;
    }

    /**
     * @param Post $data
     */
    public function normalize($data, array $includes = []): array
    {
        $output = [
            'id' =>             $data->getId(),
            'title' =>          $data->getTitle(),
            'alias' =>          $data->getAlias(),
            'description' =>    $data->getDescription(),
            'content' =>        $data->getContent(),
            'image' =>          $data->getImage(),
            'image_url' =>      !$data->hasImage() ? '' : sprintf('%s/uploads/blog/posts/images/%s', $this->parameters->get('app.base_url'), $data->getImage()),
            'is_published' =>   $data->isPublished(),
            'is_removed' =>     $data->isRemoved(),
            'created_at' =>     $data->getCreatedAt()?->format('c'),
            'published_at' =>   $data->getPublishedAt()?->format('c'),
            'updated_at' =>     $data->getUpdatedAt()?->format('c'),
            'deleted_at' =>     $data->getRemovedAt()?->format('c')
        ];

        if (in_array('author', $includes)) {
            $output['author'] = !$data->hasAuthor() || $data->getAuthor()->isRemoved() || $data->getAuthor()->isErased()
                ? null
                : $this->normalizer->normalize(UserPrimaryNormalizer::class, $data->getAuthor(), $this->extractIncludes($includes, 'author'));
        }

        if (in_array('categories', $includes)) {
            $output['categories'] = array_values(array_map(
                function (Category $category) use ($includes) {
                    return $this->normalizer->normalize(CategoryNormalizer::class, $category, $this->extractIncludes($includes, 'categories'));
                },
                array_filter($data->getCategories()->toArray(), function (Category $category) {
                    return $this->security->isGranted(CategoryVoter::ATTR_VIEW, $category);
                })
            ));
        }

        if (in_array('comments_count', $includes)) {
            $output['comments_count'] = $data->getComments()->count();
        }

        if (in_array('permissions', $includes)) {
            $output['permissions'] = array_combine(PostVoter::ATTRIBUTES, array_map(function (string $attribute) use ($data) {
                return $this->security->isGranted($attribute, $data);
            }, PostVoter::ATTRIBUTES));
        }

        return $output;
    }
}
