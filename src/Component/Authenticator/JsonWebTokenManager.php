<?php

namespace App\Component\Authenticator;

use Firebase\JWT\JWT as FirebaseJWT;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class JsonWebTokenManager
{
    /**
     * JWT encoding algorithm.
     */
    private const JWT_ENCODE_ALGORITHM = 'RS256';

    /**
     * Authorization Header name.
     */
    private const AUTHORIZATION_HEADER_NAME = 'Authorization';

    /**
     * Authorization Header value prefix.
     */
    private const AUTHORIZATION_HEADER_PREFIX = 'Bearer';

    private string  $privateKey;
    private string  $publicKey;

    public function __construct(
        private ContainerBagInterface $parameters
    )
    {
        $this->privateKey = @file_get_contents(str_replace('//', '/', $this->parameters->get('app.dir.jwt_keys') . '/private.pem')) ?: '';
        $this->publicKey =  @file_get_contents(str_replace('//', '/', $this->parameters->get('app.dir.jwt_keys') . '/public.pem')) ?: '';
    }

    /**
     * Generates access token.
     */
    public function generateToken(UserInterface $user, int $ttl): string
    {
        if (!$this->privateKey) {
            throw new \Exception('Can not generate access token because keys is not defined.');
        }

        $payload = [
            'iss' =>        $this->parameters->get('router.request_context.host'),
            'iat' =>        time(),
            'exp' =>        time() + $ttl,
            'user_id' =>    $user->getUserIdentifier()
        ];

        $accessToken = FirebaseJWT::encode($payload, $this->privateKey, self::JWT_ENCODE_ALGORITHM);

        return $accessToken;
    }

    /**
     * Extracts token from given request.
     */
    public function extractToken(Request $request): ?string
    {
        $header = $request->headers->get(self::AUTHORIZATION_HEADER_NAME);

        if (!$header) {
            return null;
        }

        if (!self::AUTHORIZATION_HEADER_PREFIX) {
            return $header;
        }

        $headerParts = explode(' ', $header);

        return (2 === count($headerParts) && 0 === strcasecmp($headerParts[0], self::AUTHORIZATION_HEADER_PREFIX))
            ? $headerParts[1]
            : null;
    }

    /**
     * Extracts payload from given token.
     */
    public function extractPayload(Request|string $token): ?array
    {
        if ($token instanceof Request) {
            $token = $this->extractToken($token);
        }

        if (!$token || !$this->publicKey) {
            return null;
        }

        try {
            $payload = FirebaseJWT::decode($token, $this->publicKey, [self::JWT_ENCODE_ALGORITHM]);
        } catch (\Exception) {
            return null;
        }

        return (array) $payload;
    }

    /**
     * Checks if request has token and it is valid.
     */
    public function hasToken(Request $request): bool
    {
        return (bool) $this->extractPayload($request);
    }
}
