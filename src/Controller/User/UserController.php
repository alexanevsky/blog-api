<?php

namespace App\Controller\User;

use App\Component\Response\JsonResponse\JsonResponse;
use App\Component\Response\JsonResponse\SuccessResponse;
use App\Controller\AbstractController;
use App\Normalizer\User\UserCollectionNormalizer;
use App\Repository\User\UserRepository;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $usersRepository
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
}
