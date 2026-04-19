<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO\Content;

/**
 * Single explanatory step displayed in the TubaPay popup.
 */
readonly class PopupStep
{
    public function __construct(
        public string $title,
        public string $description,
    ) {}

    /**
     * Create from API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: (string) ($data['title'] ?? ''),
            description: (string) ($data['description'] ?? ''),
        );
    }
}
