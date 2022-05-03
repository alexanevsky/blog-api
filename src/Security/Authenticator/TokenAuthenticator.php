<?php

namespace App\Security\Authenticator;

use App\Component\Response\JsonResponse\FailureResponse;
use App\Component\Response\JsonResponse\SuccessResponse;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class TokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository  $usersRepository,
        private TokenManager    $jwt
    )
    {}

    public function supports(Request $request): bool
    {
        return $this->jwt->hasAccessToken($request);
    }

    public function authenticate(Request $request): Passport
    {
        $payload = $this->jwt->extractPayload($request);
        $id = (int) ($payload['user_id'] ?? '');

        $badge = new UserBadge($id, function (int $id): User {
            if (!$id) {
                throw new AuthenticationException('security.messages.token_authenticaton.invalid_payload');
            }

            $user = $this->usersRepository->findOneById((int) $id);

            if (!$user) {
                throw new AuthenticationException('security.messages.user.not_found');
            }

            return $user;
        });

        $credentials = new CustomCredentials(function (array $payload, User $user): bool {
            if ($user->isBanned()) {
                throw new AuthenticationException('security.messages.user.banned');
            }

            return true;
        }, $payload);

        return new Passport($badge, $credentials);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?SuccessResponse
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): FailureResponse
    {
        return new FailureResponse($exception->getMessage());
    }
}
