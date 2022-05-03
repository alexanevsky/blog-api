<?php

namespace App\Controller;

use App\Component\Response\JsonResponse\FailureResponse;
use App\Component\Response\JsonResponse\JsonResponse;
use App\Component\Response\JsonResponse\NeedAuthResponse;
use App\Component\Response\JsonResponse\SuccessResponse;
use App\Entity\User\User;
use App\Normalizer\Management\User\UserNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/auth')]
class SecurityController extends AbstractController
{
    #[Route(path: '', methods: ['POST'], name: 'security_login')]
    public function login(): FailureResponse
    {
        return new FailureResponse('security.messages.password_authenticaton.invalid_credentials');
    }

    #[Route(path: '/refresh', methods: ['POST'], name: 'security_refresh')]
    public function refresh(): FailureResponse
    {
        return new FailureResponse('security.messages.refresh_authenticaton.missed_token');
    }

    #[Route(path: '/user', methods: ['GET'])]
    public function user(): JsonResponse
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new NeedAuthResponse('security.messages.user.need_auth');
        }

        return new SuccessResponse(data: [
            'user' => [
                'id' =>         $user->getId(),
                'username' =>   $user->getUsername()
            ]
        ]);
    }
}
