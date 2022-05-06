<?php

namespace App\Controller\User;

use App\Component\Response\JsonResponse\AccessDeniedResponse;
use App\Component\Response\JsonResponse\DeletedResponse;
use App\Component\Response\JsonResponse\FailureResponse;
use App\Component\Response\JsonResponse\JsonResponse;
use App\Component\Response\JsonResponse\NotFoundResponse;
use App\Component\Response\JsonResponse\SuccessResponse;
use App\Controller\AbstractController;
use App\Entity\User\User;
use App\Normalizer\Blog\PostMainCollectionNormalizer;
use App\Normalizer\User\UserCollectionNormalizer;
use App\Normalizer\User\UserNormalizer;
use App\Repository\Blog\PostRepository;
use App\Repository\User\UserRepository;
use App\Resolver\User\UserResolverBuilder;
use App\Security\Voter\SecurityVoter;
use App\Security\Voter\User\UserVoter;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/users')]
class UserController extends AbstractController
{
    public function __construct(
        private PostRepository      $postsRepository,
        private UserRepository      $usersRepository,
        private UserResolverBuilder $userResolverBuilder,
    )
    {}

    #[Route(path: '', methods: ['GET'])]
    public function all(): JsonResponse
    {
        [$usersOffset, $usersLimit] = [
            (int) $this->getQueryParameter('offset', 0),
            (int) $this->getQueryParameter('limit', UserRepository::PAGE_LIMIT)
        ];

        $users = $this->usersRepository->findNotRemovedPaginated($usersOffset, $usersLimit);

        return new SuccessResponse(data: [
            'users' => $this->normalize(UserCollectionNormalizer::class, $users, ['permissions']),
            'users_meta' => $users->getMeta()
        ]);
    }

    #[Route(path: '/removed', methods: ['GET'])]
    public function removed(): JsonResponse
    {
        if (!$this->isGrantedAny([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER])) {
            return new AccessDeniedResponse('users.messages.removed_users.access_denied', needAuth: !$this->isLogged());
        }

        [$usersOffset, $usersLimit] = [
            (int) $this->getQueryParameter('offset', 0),
            (int) $this->getQueryParameter('limit', UserRepository::PAGE_LIMIT)
        ];

        $users = $this->usersRepository->findRemovedPaginated($usersOffset, $usersLimit);

        return new SuccessResponse(data: [
            'users' => $this->normalize(UserCollectionNormalizer::class, $users, ['permissions']),
            'users_meta' => $users->getMeta()
        ]);
    }

    #[Route(path: '/{id<[\w-]+>}', methods: ['GET'], priority: -1)]
    public function user(int|string $id): JsonResponse
    {
        $user = $this->usersRepository->findOneByIdOrAlias($id);

        if (!$user) {
            return new NotFoundResponse('users.messages.user.not_found');
        } elseif (!$this->isGranted(UserVoter::ATTR_VIEW, $user)) {
            if ($user->isErased()) {
                return new DeletedResponse('users.messages.user.erased');
            } elseif ($user->isRemoved()) {
                return new DeletedResponse('users.messages.user.removed');
            } else {
                return new AccessDeniedResponse('users.messages.user.access_denied', needAuth: !$this->isLogged());
            }
        }

        return new SuccessResponse(data: [
            'user' => $this->normalize(UserNormalizer::class, $user, ['permissions'])
        ]);
    }

    #[Route(path: '', methods: ['POST'])]
    #[Route(path: '/create', methods: ['GET', 'POST'])]
    public function createUser(): JsonResponse
    {
        if (!$this->isGranted(SecurityVoter::ATTR_CREATE_USER)) {
            return new AccessDeniedResponse('users.messages.user_create.access_denied', needAuth: !$this->isLogged());
        }

        $user = new User();
        $resolver = $this->userResolverBuilder->build($user);

        if ($this->isRequestMethod('GET')) {
            return new SuccessResponse(data: [
                'fields' => $resolver->getRequirements()
            ]);
        }

        $result = $resolver->resolve($this->decodeRequest());

        if (!$result->isValid()) {
            return new FailureResponse('users.messages.user_create.failed', errors: $result->getFirstErrors());
        }

        $result->handleEntity();

        $user->setCreatedBy($this->getUser());

        $this->getDoctrineManager()->persist($user);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('users.messages.user_create.created', data: [
            'user' => $this->normalize(UserNormalizer::class, $user)
        ]);
    }

    #[Route(path: '/{id<[\w-]+>}', methods: ['PATCH'])]
    #[Route(path: '/{id<[\w-]+>}/update', methods: ['GET', 'POST'])]
    public function update(int|string $id): JsonResponse
    {
        $user = $this->usersRepository->findOneByIdOrAlias($id);

        if (!$user) {
            return new NotFoundResponse('users.messages.user.not_found');
        } elseif (!$this->isGranted(UserVoter::ATTR_UPDATE, $user)) {
            return new AccessDeniedResponse('users.messages.user_update.access_denied', needAuth: !$this->isLogged());
        }

        $resolver = $this->userResolverBuilder->build($user);

        if ($this->isRequestMethod('GET')) {
            return new SuccessResponse(data: [
                'fields' => $resolver->getRequirements()
            ]);
        }

        $result = $resolver->resolve($this->decodeRequest());

        if (!$result->isValid()) {
            return new FailureResponse('users.messages.user_update.failed', errors: $result->getFirstErrors());
        }

        $result->handleEntity();

        $user->setUpdatedNow();

        $this->getDoctrineManager()->persist($user);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('users.messages.user_update.updated', data: [
            'user' => $this->normalize(UserNormalizer::class, $user)
        ]);
    }

    #[Route(path: '/{id<[\w-]+>}/remove', methods: ['POST'])]
    public function remove(int|string $id): JsonResponse
    {
        $user = $this->usersRepository->findOneByIdOrAlias($id);

        if (!$user) {
            return new NotFoundResponse('users.messages.user.not_found');
        } elseif (!$this->isGranted(UserVoter::ATTR_REMOVE, $user)) {
            return new AccessDeniedResponse('users.messages.user_remove.access_denied', needAuth: !$this->isLogged());
        }

        $user
            ->setRemoved(true)
            ->setRemovedNow();

        $this->getDoctrineManager()->persist($user);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('users.messages.user_remove.removed');
    }

    #[Route(path: '/{id<[\w-]+>}/restore', methods: ['POST'])]
    public function restore(int|string $id): JsonResponse
    {
        $user = $this->usersRepository->findOneByIdOrAlias($id);

        if (!$user) {
            return new NotFoundResponse('users.messages.user.not_found');
        } elseif (!$this->isGranted(UserVoter::ATTR_RESTORE, $user)) {
            return new AccessDeniedResponse('users.messages.user_restore.access_denied', needAuth: !$this->isLogged());
        }

        $user
            ->setRemoved(false)
            ->setRemovedAt(null);

        $this->getDoctrineManager()->persist($user);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('users.messages.user_restore.restored');
    }

    #[Route(path: '/{id<[\w-]+>}/erase', methods: ['POST'])]
    public function erase(int|string $id)
    {
        $user = $this->usersRepository->findOneByIdOrAlias($id);

        if (!$user) {
            return new NotFoundResponse('users.messages.user.not_found');
        } elseif (!$this->isGranted(UserVoter::ATTR_ERASE, $user)) {
            return new AccessDeniedResponse('users.messages.user_erase.access_denied', needAuth: !$this->isLogged());
        }

        $user->erase();

        $this->getDoctrineManager()->persist($user);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('users.messages.user_erase.erased');
    }

    #[Route(path: '/{id<[\w-]+>}/blog/posts', methods: ['GET'])]
    public function blogPosts(int|string $id): JsonResponse
    {
        $user = $this->usersRepository->findOneByIdOrAlias($id);

        if (!$user) {
            return new NotFoundResponse('users.messages.user.not_found');
        } elseif (!$this->isGranted(UserVoter::ATTR_VIEW, $user)) {
            return new AccessDeniedResponse('users.messages.user.access_denied', needAuth: !$this->isLogged());
        }

        [$postsOffset, $postsLimit] = [
            (int) $this->getQueryParameter('offset', 0),
            (int) $this->getQueryParameter('limit', PostRepository::PAGE_LIMIT)
        ];

        $posts = $this->postsRepository->findByAuthorPublishedPaginated($user, $postsOffset, $postsLimit);

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
