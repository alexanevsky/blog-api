<?php

namespace App\Controller\User;

use App\Component\Response\JsonResponse\AccessDeniedResponse;
use App\Component\Response\JsonResponse\DeletedResponse;
use App\Component\Response\JsonResponse\JsonResponse;
use App\Component\Response\JsonResponse\NotFoundResponse;
use App\Component\Response\JsonResponse\SuccessResponse;
use App\Controller\AbstractController;
use App\Entity\User\User;
use App\Normalizer\User\UserCollectionNormalizer;
use App\Normalizer\User\UserNormalizer;
use App\Repository\User\UserRepository;
use App\Resolver\User\UserResolverBuilder;
use App\Security\Voter\User\UserVoter;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/users')]
class UserController extends AbstractController
{
    public function __construct(
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

        $users = $this->usersRepository->findNotDeletedPaginated($usersOffset, $usersLimit);

        return new SuccessResponse(data: [
            'users' => $this->normalize(UserCollectionNormalizer::class, $users, ['permissions']),
            'users_meta' => $users->getMeta()
        ]);
    }

    #[Route(path: '/banned', methods: ['GET'])]
    public function banned(): JsonResponse
    {
        if (!$this->isGrantedAny([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER])) {
            return new AccessDeniedResponse('users.messages.banned_users.access_denied', needAuth: !$this->isLogged());
        }

        [$usersOffset, $usersLimit] = [
            (int) $this->getQueryParameter('offset', 0),
            (int) $this->getQueryParameter('limit', UserRepository::PAGE_LIMIT)
        ];

        $users = $this->usersRepository->findNotDeletedBannedPaginated($usersOffset, $usersLimit);

        return new SuccessResponse(data: [
            'users' => $this->normalize(UserCollectionNormalizer::class, $users, ['permissions']),
            'users_meta' => $users->getMeta()
        ]);
    }

    #[Route(path: '/deleted', methods: ['GET'])]
    public function deleted(): JsonResponse
    {
        if (!$this->isGrantedAny([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER])) {
            return new AccessDeniedResponse('users.messages.deleted_users.access_denied', needAuth: !$this->isLogged());
        }

        [$usersOffset, $usersLimit] = [
            (int) $this->getQueryParameter('offset', 0),
            (int) $this->getQueryParameter('limit', UserRepository::PAGE_LIMIT)
        ];

        $users = $this->usersRepository->findDeletedPaginated($usersOffset, $usersLimit);

        return new SuccessResponse(data: [
            'users' => $this->normalize(UserCollectionNormalizer::class, $users, ['permissions']),
            'users_meta' => $users->getMeta()
        ]);
    }

    #[Route(path: '/users/{id<[\w-]+>}', methods: ['GET'], priority: -1)]
    public function user(int|string $id): JsonResponse
    {
        $user = $this->usersRepository->findOneByIdOrAlias($id);

        if (!$user) {
            return new NotFoundResponse('users.messages.user.not_found');
        } elseif (!$this->isGranted(UserVoter::ATTR_VIEW, $user)) {
            if ($user->isErased()) {
                return new DeletedResponse('users.messages.user.erased');
            } elseif ($user->isDeleted()) {
                return new DeletedResponse('users.messages.user.deleted');
            } else {
                return new AccessDeniedResponse('users.messages.user.access_denied', needAuth: !$this->isLogged());
            }
        }

        return new SuccessResponse(data: [
            'user' => $this->normalize(UserNormalizer::class, $user, ['permissions'])
        ]);
    }
}
