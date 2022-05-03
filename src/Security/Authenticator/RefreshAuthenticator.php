<?php

namespace App\Security\Authenticator;

use App\Component\Response\JsonResponse\FailureResponse;
use App\Component\Response\JsonResponse\SuccessResponse;
use App\Entity\User\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class RefreshAuthenticator extends AbstractAuthenticator
{
    /**
     * Route to handle refresh.
     */
    public const REFRESH_ROUTE = 'security_refresh';

    public function __construct(
        private TokenManager $jwt
    )
    {}

    public function supports(Request $request): ?bool
    {
        return self::REFRESH_ROUTE === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $data = $request->toArray();

        $badge = new UserBadge((string) ($data['refresh_token'] ?? ($data['refreshToken'] ?? '')), function (string $token): User {
            if (!$token) {
                throw new AuthenticationException('security.messages.refresh_authenticaton.missed_token');
            }

            $refreshToken = $this->jwt->extractRefreshToken($token);

            if (!$refreshToken) {
                throw new AuthenticationException('security.messages.refresh_authenticaton.invalid_token');
            }

            return $refreshToken->getUser();
        });

        $credentials = new CustomCredentials(function ($credentials, User $user): bool {
            if ($user->isBanned()) {
                throw new AuthenticationException('security.messages.user.banned');
            }

            return true;
        }, $data);

        return new Passport($badge, $credentials);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?SuccessResponse
    {
        $tokens = $this->jwt->generateTokens($token->getUser());

        return new SuccessResponse('security.messages.refresh_authenticaton.authenticated', data: $tokens);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): FailureResponse
    {
        return new FailureResponse($exception->getMessage());
    }
}
