<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO;

/**
 * Result of checking partner API credentials against TubaPay.
 */
readonly class ConnectionStatus
{
    public function __construct(
        public bool $successful,
        public string $message,
        public ?TokenResponse $token = null,
    ) {}

    public static function successful(TokenResponse $token): self
    {
        return new self(
            successful: true,
            message: 'Authorization successful.',
            token: $token,
        );
    }

    public static function failed(string $message): self
    {
        return new self(
            successful: false,
            message: $message,
        );
    }
}
