<?php

namespace App\Security\Authenticator;

use App\Component\Authenticator\JsonWebTokenManager;
use App\Component\Authenticator\TokenManagerInterface;
use App\Entity\User\User;
use App\Entity\User\UserRefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;

class TokenManager implements TokenManagerInterface
{
    private int $accessTokenTtl;
    private int $refreshTokenTtl;

    public function __construct(
        private ContainerBagInterface   $parameters,
        private EntityManagerInterface  $em,
        private JsonWebTokenManager     $jwt
    )
    {
        $this->accessTokenTtl = $this->parameters->get('app.jwt.access_token_ttl');
        $this->refreshTokenTtl = $this->parameters->get('app.jwt.refresh_token_ttl');
    }

    /**
     * @param User $user
     */
    public function generateTokens($user, ?Request $request = null): array
    {
        $tokens = [
            'access_token' =>    null,
            'refresh_token' =>   null
        ];

        try {
            $tokens['access_token'] = $this->jwt->generateToken($user, $this->accessTokenTtl);
            $tokens['refresh_token'] = $this->createRefreshToken($user, $request)->getToken();
        } catch (\Exception) {
            return $tokens;
        }

        return $tokens;
    }

    public function extractPayload(Request $request): ?array
    {
        return $this->jwt->extractPayload($request);
    }

    public function hasAccessToken(Request $request): bool
    {
        return $this->jwt->hasToken($request);
    }

    public function extractRefreshToken(string $token): ?UserRefreshToken
    {
        /** @var ?UserRefreshToken */
        $refreshToken = $this->em->getRepository(UserRefreshToken::class)->findOneBy(['token' => $token]);
        $datetime = new \DateTime();

        if (!$refreshToken || $refreshToken->isUsed() || $refreshToken->getExpiresAt() < $datetime) {
            return null;
        }

        $refreshToken
            ->setExpiresAt($datetime)
            ->setUsed(true);

        $this->em->persist($refreshToken);
        $this->em->flush();

        return $refreshToken;
    }

    /**
     * Creates refresh tokens for given user.
     */
    private function createRefreshToken(User $user, ?Request $request = null): UserRefreshToken
    {
        $refreshToken = (new UserRefreshToken())
            ->setUser($user)
            ->setExpiresAt((new \DateTime())->modify(sprintf('+%s seconds', $this->refreshTokenTtl)));

        if (null !== $request) {
            $refreshToken->setIp($request->server->get('REMOTE_ADDR'));
            $refreshToken->setUseragent($request->server->get('HTTP_USER_AGENT'));
        }

        $this->em->persist($refreshToken);
        $this->em->flush();

        return $refreshToken;
    }
}
