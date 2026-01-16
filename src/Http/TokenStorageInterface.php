<?php

declare(strict_types=1);

namespace Core45\TubaPay\Http;

/**
 * Interface for storing and retrieving OAuth tokens.
 *
 * Implement this interface to persist tokens across requests.
 * The default InMemoryTokenStorage is suitable for single-request scenarios.
 */
interface TokenStorageInterface
{
    /**
     * Get the stored access token.
     *
     * @return string|null The token or null if not stored
     */
    public function getToken(): ?string;

    /**
     * Store the access token with expiration.
     *
     * @param string $token The access token to store
     * @param int $expiresIn Expiration time in seconds from now
     */
    public function setToken(string $token, int $expiresIn): void;

    /**
     * Check if a valid (non-expired) token exists.
     */
    public function hasValidToken(): bool;

    /**
     * Clear the stored token.
     */
    public function clearToken(): void;
}
