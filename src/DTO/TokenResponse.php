<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO;

/**
 * TubaPay partner authentication token response.
 */
readonly class TokenResponse
{
    public function __construct(
        public string $accessToken,
        public int $expiresIn,
        public ?string $refreshToken = null,
        public ?string $expiresAt = null,
    ) {}

    /**
     * Create from API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $response = $data['result']['response'] ?? $data;
        $response = is_array($response) ? $response : [];

        $expiresAt = isset($response['expires']) ? (string) $response['expires'] : null;

        return new self(
            accessToken: (string) ($response['accessToken'] ?? $response['token'] ?? ''),
            expiresIn: self::extractExpiresIn($response),
            refreshToken: isset($response['refreshToken']) ? (string) $response['refreshToken'] : null,
            expiresAt: $expiresAt,
        );
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private static function extractExpiresIn(array $response): int
    {
        $expiresIn = $response['expiresIn'] ?? null;

        if (is_numeric($expiresIn)) {
            return max(0, (int) $expiresIn);
        }

        $expires = $response['expires'] ?? null;

        if (is_numeric($expires)) {
            $expires = (int) $expires;

            return $expires > time() ? $expires - time() : max(0, $expires);
        }

        if (is_string($expires) && $expires !== '') {
            $timestamp = strtotime(substr($expires, 0, 19));

            if ($timestamp !== false) {
                return max(0, $timestamp - time());
            }
        }

        return 3600;
    }
}
