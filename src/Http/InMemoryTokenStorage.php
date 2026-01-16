<?php

declare(strict_types=1);

namespace Core45\TubaPay\Http;

/**
 * In-memory token storage for single-request scenarios.
 *
 * This storage does not persist tokens between PHP requests.
 * For production use in web applications, implement TokenStorageInterface
 * with a persistent backend (database, cache, session, etc.).
 */
final class InMemoryTokenStorage implements TokenStorageInterface
{
    private ?string $token = null;
    private ?int $expiresAt = null;

    /**
     * Buffer time (in seconds) before actual expiration to refresh token.
     */
    private const EXPIRATION_BUFFER = 60;

    public function getToken(): ?string
    {
        if (!$this->hasValidToken()) {
            return null;
        }

        return $this->token;
    }

    public function setToken(string $token, int $expiresIn): void
    {
        $this->token = $token;
        $this->expiresAt = time() + $expiresIn;
    }

    public function hasValidToken(): bool
    {
        if ($this->token === null || $this->expiresAt === null) {
            return false;
        }

        // Consider token invalid if it expires within the buffer time
        return time() < ($this->expiresAt - self::EXPIRATION_BUFFER);
    }

    public function clearToken(): void
    {
        $this->token = null;
        $this->expiresAt = null;
    }
}
