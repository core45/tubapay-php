<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO;

/**
 * Integration metadata sent with transaction creation requests.
 */
readonly class TransactionMetadata
{
    /**
     * @param  array<string, scalar|null>  $additional
     */
    public function __construct(
        public ?string $appVersion = null,
        public ?string $appDetailedVersion = null,
        public ?string $source = null,
        public array $additional = [],
    ) {}

    public static function forIntegration(
        string $source,
        ?string $appVersion = null,
        ?string $appDetailedVersion = null,
    ): self {
        return new self(
            appVersion: $appVersion,
            appDetailedVersion: $appDetailedVersion,
            source: $source,
        );
    }

    /**
     * Convert to order metadata fields accepted by TubaPay.
     *
     * @return array<string, scalar>
     */
    public function toArray(): array
    {
        $metadata = array_filter([
            'appVersion' => $this->appVersion,
            'appDetailedVersion' => $this->appDetailedVersion,
            'source' => $this->source,
        ], static fn ($value): bool => $value !== null && $value !== '');

        foreach ($this->additional as $key => $value) {
            if ($value !== null && $value !== '') {
                $metadata[$key] = $value;
            }
        }

        return $metadata;
    }
}
