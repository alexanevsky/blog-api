<?php

namespace App\Resolver\Blog;

use Alexanevsky\DataResolver\EntityResolver;
use App\Component\Validator\ConstraintBuilder;
use App\Entity\Blog\Category;
use App\Entity\Blog\Post;
use App\Repository\Blog\CategoryRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraint;

class PostResolverBuilder
{
    private const OPTIONS_TYPES = [
        'title' =>                  'string',
        'alias' =>                  'string',
        'categories' =>             Category::class . '[]',
        'description' =>            'array',
        'content' =>                'array',
        'is_published' =>           'boolean',
        'update_published_at' =>    'boolean'
    ];

    private const NOT_MAPPED = [
        'update_published_at'
    ];

    public function __construct(
        private CategoryRepository  $categoriesRepository,
        private ConstraintBuilder   $constraints,
        private SluggerInterface    $slugger
    )
    {}

    public function build(Post $post): EntityResolver
    {
        $resolver = new EntityResolver();

        foreach ($this->getOptionsTypes($post) as $name => $type) {
            $resolver->define($name, $type);

            if (in_array($name, self::NOT_MAPPED)) {
                $resolver->get($name)->setMapped(false);
            }

            if ($normalizer = $this->getNormalizer($name, $post)) {
                $resolver->get($name)->setNormalizer($normalizer);
            }

            if ($defaultConverter = $this->getDefaultConverter($name, $post)) {
                $resolver->get($name)->setDefaultConverter($defaultConverter);
            }

            if ($constraints = $this->getConstraints($name, $post)) {
                $resolver->get($name)->setConstraints($constraints);
            }
        }

        $resolver->setEntityNormalizer(function (Post $post, array $data) {
            if (!empty($data['update_published_at'])) {
                $post->setPublishedNow();
            }
        });

        $resolver->handleEntity($post);

        return $resolver;
    }

    private function getOptionsTypes(Post $post): array
    {
        $options = self::OPTIONS_TYPES;

        if (!$post->hasId()) {
            unset($options['update_published_at']);
        }

        return $options;
    }

    private function getNormalizer(string $option, Post $post): ?\Closure
    {
        $normalizers = [
            'alias' => function ($alias) {
                return !$alias ? '' : strtolower($this->slugger->slug($alias)->toString());
            },
            'categories' => function (array $categories) {
                return array_filter(array_map(function ($category) {
                    return $category instanceof Category ? $category : $this->categoriesRepository->findOneBy(['id' => (int) $category]);
                }, $categories));
            }
        ];

        return $normalizers[$option] ?? null;
    }

    private function getDefaultConverter(string $name, Post $post): ?\Closure
    {
        $converters = [
            'categories' => function (Collection|array $categories) {
                return array_values(array_map(function (Category $category) {
                    return $category->getId();
                }, $categories instanceof Collection ? $categories->toArray() : $categories));
            }
        ];

        return $converters[$name] ?? null;
    }

    /**
     * @return Constraint[]
     */
    private function getConstraints(string $option, Post $post): array
    {
        $constraints = [
            'title' => [
                $this->constraints->notBlank('blog_posts.errors.title.empty')
            ],
            'alias' => [
                $this->constraints->notNumeric('blog_posts.errors.alias.numeric')
            ]
        ];

        return $constraints[$option] ?? [];
    }
}
