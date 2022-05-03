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

class PasswordAuthenticator extends AbstractAuthenticator
{
    /**
     * Route to handle authentication.
     */
    public const LOGIN_ROUTE = 'security_login';

    public function __construct(
        private UserRepository  $usersRepository,
        private TokenManager    $jwt
    )
    {}

    public function supports(Request $request): ?bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route') && $request->isMethod('POST') ? null : false;
    }

    public function authenticate(Request $request): Passport
    {
        $data = $request->toArray();

        $badge = new UserBadge(json_encode($data), function (string $credentials): User {
            $credentials = json_decode($credentials, true);

            if (!empty($credentials['email'])) {
                $user = $this->usersRepository->findOneByEmail($credentials['email']);
            } else {
                throw new AuthenticationException('security.messages.password_authenticaton.invalid_credentials');
            }

            if (!$user) {
                throw new AuthenticationException('security.messages.user.not_found');
            }

            return $user;
        });

        $credentials = new CustomCredentials(function (?string $password, User $user): bool {
            if (!$password) {
                throw new AuthenticationException('security.messages.password_authenticaton.password_is_empty');
            } elseif (true !== $user->verifyPassword($password)) {
                throw new AuthenticationException('security.messages.password_authenticaton.password_not_verified');
            } elseif ($user->isBanned()) {
                throw new AuthenticationException('security.messages.user.banned');
            }

            return true;
        }, $data['password'] ?? null);

        return new Passport($badge, $credentials);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?SuccessResponse
    {
        $tokens = $this->jwt->generateTokens($token->getUser());

        return new SuccessResponse('security.messages.password_authenticaton.authenticated', data: $tokens);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): FailureResponse
    {
        return new FailureResponse($exception->getMessage());
    }
}
