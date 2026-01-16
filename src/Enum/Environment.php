<?php

declare(strict_types=1);

namespace Core45\TubaPay\Enum;

/**
 * TubaPay API environment.
 */
enum Environment: string
{
    case Test = 'test';
    case Production = 'production';

    /**
     * Get the base URL for this environment.
     */
    public function getBaseUrl(): string
    {
        return match ($this) {
            self::Test => 'https://tubapay-test.bacca.pl',
            self::Production => 'https://tubapay.pl',
        };
    }

    /**
     * Create from string value (case-insensitive).
     */
    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'test', 'testing', 'sandbox' => self::Test,
            'production', 'prod', 'live' => self::Production,
            default => throw new \InvalidArgumentException(
                sprintf('Invalid environment value: "%s". Expected "test" or "production".', $value)
            ),
        };
    }
}
