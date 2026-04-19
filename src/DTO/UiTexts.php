<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO;

/**
 * UI labels returned by TubaPay for checkout and promotional elements.
 */
readonly class UiTexts
{
    /**
     * @param  array<string, string>  $texts
     */
    public function __construct(
        public array $texts,
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

        $texts = [];
        foreach ($response as $key => $value) {
            if (is_scalar($value)) {
                $texts[(string) $key] = (string) $value;
            }
        }

        return new self($texts);
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->texts[$key] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->texts;
    }
}
