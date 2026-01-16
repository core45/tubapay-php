<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO;

/**
 * Customer data for creating a TubaPay transaction.
 */
readonly class Customer
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public string $street,
        public string $zipCode,
        public string $town,
        public ?string $streetNumber = null,
        public ?string $flatNumber = null,
    ) {}

    /**
     * Convert to array for API request.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return array_filter([
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'street' => $this->street,
            'streetNumber' => $this->streetNumber,
            'flatNumber' => $this->flatNumber,
            'zipCode' => $this->zipCode,
            'town' => $this->town,
        ], fn ($value) => $value !== null);
    }

    /**
     * Create from array (e.g., webhook payload).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: (string) ($data['firstName'] ?? ''),
            lastName: (string) ($data['lastName'] ?? $data['surName'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            phone: (string) ($data['phone'] ?? $data['cellphone'] ?? ''),
            street: (string) ($data['street'] ?? ''),
            zipCode: (string) ($data['zipCode'] ?? ''),
            town: (string) ($data['town'] ?? ''),
            streetNumber: isset($data['streetNumber']) ? (string) $data['streetNumber'] : null,
            flatNumber: isset($data['flatNumber']) ? (string) $data['flatNumber'] : null,
        );
    }
}
