<?php

namespace App\Component\Authenticator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

interface TokenManagerInterface
{
    /**
     * Generates access and refresh tokens.
     */
    public function generateTokens(UserInterface $user, ?Request $request = null): array;

    /**
     * Extracts token's payload from given request.
     */
    public function extractPayload(Request $request): ?array;

    /**
     * Checks if request has token and it is valid.
     */
    public function hasAccessToken(Request $request): bool;

    /**
     * Finds the refresh token entity and set it used.
     */
    public function extractRefreshToken(string $token): ?RefreshTokenEntityInterface;
}
