<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO;

/**
 * Order item data for creating a TubaPay transaction.
 */
readonly class OrderItem
{
    public function __construct(
        public string $name,
        public float $totalValue,
        public string $brand = '',
        public string $description = '',
        public ?string $externalRef = null,
    ) {}

    /**
     * Convert to array for API request.
     *
     * @return array<string, string|float|null>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'brand' => $this->brand,
            'description' => $this->description,
            'totalValue' => $this->totalValue,
            'externalRef' => $this->externalRef,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Create from array (e.g., webhook payload).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            totalValue: (float) ($data['totalValue'] ?? $data['netValue'] ?? 0.0),
            brand: (string) ($data['brand'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            externalRef: isset($data['externalRef']) ? (string) $data['externalRef'] : null,
        );
    }
}
