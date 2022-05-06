<?php

namespace App\Resolver\Blog;

use Alexanevsky\DataResolver\EntityResolver;
use App\Component\Validator\ConstraintBuilder;
use App\Entity\Blog\Category;
use App\Repository\Blog\CategoryRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraint;

class CategoryResolverBuilder
{
    private const OPTIONS_TYPES = [
        'name' =>           'string',
        'alias' =>          'string',
        'description' =>    'array',
        'is_active' =>      'boolean',
        'sorting' =>        'int'
    ];

    private const NULLABLE_OPTIONS = [
        'alias'
    ];

    public function __construct(
        private CategoryRepository      $categoriesRepository,
        private ConstraintBuilder       $constraints,
        private SluggerInterface        $slugger
    )
    {}

    public function build(Category $category): EntityResolver
    {
        $resolver = new EntityResolver();

        foreach (self::OPTIONS_TYPES as $name => $type) {
            $resolver->define($name, $type, in_array($name, self::NULLABLE_OPTIONS));

            if ($normalizer = $this->getNormalizer($name, $category)) {
                $resolver->get($name)->setNormalizer($normalizer);
            }

            if ($constraints = $this->getConstraints($name, $category)) {
                $resolver->get($name)->setConstraints($constraints);
            }

            if ($validators = $this->getValidators($name, $category)) {
                foreach ($validators as $validatorError => $validator) {
                    $resolver->get($name)->addValidator($validator, $validatorError);
                }
            }
        }

        $resolver->handleEntity($category);

        return $resolver;
    }

    private function getNormalizer(string $option, Category $category): ?\Closure
    {
        $normalizers = [
            'alias' => function ($alias) {
                return !$alias ? null : strtolower($this->slugger->slug($alias)->toString());
            }
        ];

        return $normalizers[$option] ?? null;
    }

    /**
     * @return Constraint[]
     */
    private function getConstraints(string $option, Category $category): array
    {
        $constraints = [
            'name' => [
                $this->constraints->notBlank('blog_categories.errors.name.empty')
            ],
            'alias' => [
                $this->constraints->notBlank('blog_categories.errors.alias.empty'),
                $this->constraints->notNumeric('blog_categories.errors.alias.numeric'),
                $this->constraints->minLength(Category::ALIAS_MINLENGTH, 'blog_categories.errors.alias.short'),
                $this->constraints->maxLength(Category::ALIAS_MAXLENGTH, 'blog_categories.errors.alias.long')
            ]
        ];

        return $constraints[$option] ?? [];
    }

    /**
     * @return \Closure[]
     */
    private function getValidators(string $option, Category $category): array
    {
        $validators = [
            'name' => [
                'blog_categories.errors.name.exists' => function ($name, $default) use ($category) {
                    $found = (!$name || $name === $default) ? null : $this->categoriesRepository->findOneBy(['name' => $name]);

                    return (!$found || $found === $category) ? true : false;
                }
            ],
            'alias' => [
                'blog_categories.errors.alias.exists' => function ($alias, $default) use ($category) {
                    $found = (!$alias || $alias === $default) ? null : $this->categoriesRepository->findOneBy(['alias' => $alias]);

                    return (!$found || $found === $category) ? true : false;
                }
            ]
        ];

        return $validators[$option] ?? [];
    }
}
