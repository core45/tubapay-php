<?php

declare(strict_types=1);

namespace Core45\TubaPay\Exception;

/**
 * Exception thrown when authentication with TubaPay API fails.
 *
 * This can happen when:
 * - Invalid client credentials (clientId/clientSecret)
 * - Expired or invalid token
 * - Network issues during authentication
 */
class AuthenticationException extends TubaPayException
{
    /**
     * Create exception for invalid credentials.
     */
    public static function invalidCredentials(?string $message = null): self
    {
        return new self(
            $message ?? 'Authentication failed: Invalid client credentials.',
            401,
            context: ['reason' => 'invalid_credentials']
        );
    }

    /**
     * Create exception for expired token.
     */
    public static function tokenExpired(): self
    {
        return new self(
            'Authentication failed: Token has expired.',
            401,
            context: ['reason' => 'token_expired']
        );
    }

    /**
     * Create exception for missing credentials.
     */
    public static function missingCredentials(): self
    {
        return new self(
            'Authentication failed: Client ID and secret are required.',
            400,
            context: ['reason' => 'missing_credentials']
        );
    }
}
