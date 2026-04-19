<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO;

/**
 * Consent required or offered by TubaPay for a checkout offer.
 */
readonly class Consent
{
    public function __construct(
        /** Technical consent identifier submitted in acceptedConsents. */
        public string $type,
        /** Human-readable consent text returned by TubaPay. */
        public string $title,
        /** Whether the consent is optional. */
        public bool $optional,
    ) {}

    /**
     * Create from API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? ''),
            title: (string) ($data['title'] ?? $data['label'] ?? ''),
            optional: (bool) ($data['optional'] ?? false),
        );
    }

    /**
     * Convert to array for application-level rendering.
     *
     * @return array{type: string, title: string, optional: bool, required: bool}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'optional' => $this->optional,
            'required' => $this->isRequired(),
        ];
    }

    /**
     * Check whether the consent must be accepted before transaction creation.
     */
    public function isRequired(): bool
    {
        return ! $this->optional;
    }
}
