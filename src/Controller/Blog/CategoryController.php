<?php

namespace App\Controller\Blog;

use App\Component\Response\JsonResponse\AccessDeniedResponse;
use App\Component\Response\JsonResponse\FailureResponse;
use App\Component\Response\JsonResponse\JsonResponse;
use App\Component\Response\JsonResponse\NotFoundResponse;
use App\Component\Response\JsonResponse\SuccessResponse;
use App\Controller\AbstractController;
use App\Entity\Blog\Category;
use App\Entity\User\User;
use App\Normalizer\Blog\CategoryCollectionNormalizer;
use App\Normalizer\Blog\CategoryNormalizer;
use App\Normalizer\Blog\PostMainCollectionNormalizer;
use App\Repository\Blog\CategoryRepository;
use App\Repository\Blog\PostRepository;
use App\Resolver\Blog\CategoryResolverBuilder;
use App\Security\Voter\Blog\CategoryVoter;
use App\Security\Voter\SecurityVoter;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/blog/categories')]
class CategoryController extends AbstractController
{
    public function __construct(
        private CategoryRepository      $categoriesRepository,
        private CategoryResolverBuilder $categoryResolverBuilder,
        private PostRepository          $postsRepository
    )
    {}

    #[Route(path: '', methods: ['GET'])]
    public function all(): JsonResponse
    {
        $categories = $this->isGrantedAny([User::ROLE_ADMIN, User::ROLE_BLOG_MANAGER])
            ? $this->categoriesRepository->findAll()
            : $this->categoriesRepository->findActive();

        return new SuccessResponse(data: [
            'categories' => $this->normalize(CategoryCollectionNormalizer::class, $categories, ['permissions'])
        ]);
    }

    #[Route(path: '/{id<[\w-]+>}', methods: ['GET'], priority: -1)]
    public function category(int|string $id): JsonResponse
    {
        $category = $this->categoriesRepository->findOneByIdOrAlias($id);

        if (!$category) {
            return new NotFoundResponse('blog_categories.messages.category.not_found');
        } elseif (!$this->isGranted(CategoryVoter::ATTR_VIEW, $category)) {
            return new AccessDeniedResponse('blog_categories.messages.category.access_denied', needAuth: !$this->isLogged());
        }

        return new SuccessResponse(data: [
            'category' => $this->normalize(CategoryNormalizer::class, $category, ['permissions'])
        ]);
    }

    #[Route(path: '', methods: ['POST'])]
    #[Route(path: '/create', methods: ['GET', 'POST'])]
    public function create(): JsonResponse
    {
        if (!$this->isGranted(SecurityVoter::ATTR_CREATE_BLOG_CATEGORY)) {
            return new AccessDeniedResponse('blog_categories.messages.category_create.access_denied', needAuth: !$this->isLogged());
        }

        $category = new Category();
        $resolver = $this->categoryResolverBuilder->build($category);

        if ($this->isRequestMethod('GET')) {
            return new SuccessResponse(data: [
                'fields' => $resolver->getRequirements()
            ]);
        }

        $result = $resolver->resolve($this->decodeRequest());

        if (!$result->isValid()) {
            return new FailureResponse('blog_categories.messages.category_create.failed', errors: $result->getFirstErrors());
        }

        $result->handleEntity();

        $this->getDoctrineManager()->persist($category);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_categories.messages.category_create.created', data: [
            'category' => $this->normalize(CategoryNormalizer::class, $category)
        ]);
    }

    #[Route(path: '/{id<[\w-]+>}', methods: ['PATCH'])]
    #[Route(path: '/{id<[\w-]+>}/update', methods: ['GET', 'POST'])]
    public function update(int|string $id): JsonResponse
    {
        $category = $this->categoriesRepository->findOneByIdOrAlias($id);

        if (!$category) {
            return new NotFoundResponse('blog_categories.messages.category.not_found');
        } elseif (!$this->isGranted(CategoryVoter::ATTR_UPDATE, $category)) {
            return new AccessDeniedResponse('blog_categories.messages.category_update.access_denied', needAuth: !$this->isLogged());
        }

        $resolver = $this->categoryResolverBuilder->build($category);

        if ($this->isRequestMethod('GET')) {
            return new SuccessResponse(data: [
                'fields' => $resolver->getRequirements()
            ]);
        }

        $result = $resolver->resolve($this->decodeRequest());

        if (!$result->isValid()) {
            return new FailureResponse('blog_categories.messages.category_update.failed', errors: $result->getFirstErrors());
        }

        $result->handleEntity();

        $this->getDoctrineManager()->persist($category);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_categories.messages.category_update.updated', data: [
            'category' => $this->normalize(CategoryNormalizer::class, $category)
        ]);
    }

    #[Route(path: '/{id<[\w-]+>}', methods: ['DELETE'])]
    #[Route(path: '/{id<[\w-]+>}/delete', methods: ['POST'])]
    public function delete(int|string $id): JsonResponse
    {
        $category = $this->categoriesRepository->findOneByIdOrAlias($id);

        if (!$category) {
            return new NotFoundResponse('blog_categories.messages.category.not_found');
        } elseif (!$this->isGranted(CategoryVoter::ATTR_DELETE, $category)) {
            return new AccessDeniedResponse('blog_categories.messages.category_delete.access_denied', needAuth: !$this->isLogged());
        }

        $this->getDoctrineManager()->remove($category);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_categories.messages.category_delete.deleted');
    }

    #[Route(path: '/{id<[\w-]+>}/posts', methods: ['GET'])]
    public function posts(int|string $id): JsonResponse
    {
        $category = $this->categoriesRepository->findOneByIdOrAlias($id);

        if (!$category) {
            return new NotFoundResponse('blog_categories.messages.category.not_found');
        } elseif (!$this->isGranted(CategoryVoter::ATTR_VIEW, $category)) {
            return new AccessDeniedResponse('blog_categories.messages.category.access_denied', needAuth: !$this->isLogged());
        }

        [$postsOffset, $postsLimit] = [
            (int) $this->getQueryParameter('offset', 0),
            (int) $this->getQueryParameter('limit', PostRepository::PAGE_LIMIT)
        ];

        $posts = $this->postsRepository->findByCategoryPublishedPaginated($category, $postsOffset, $postsLimit);

        return new SuccessResponse(data: [
            'posts' => $this->normalize(PostMainCollectionNormalizer::class, $posts, [
                'author',
                'categories',
                'permissions'
            ]),
            'posts_meta' => $posts->getMeta()
        ]);
    }
}
