<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO;

/**
 * TubaPay transaction response.
 */
readonly class Transaction
{
    public function __construct(
        /** Unique transaction ID in TubaPay system. */
        public string $transactionId,
        /** URL to redirect customer to complete the transaction. */
        public string $transactionLink,
        /** API response reference ID. */
        public string $referenceId,
    ) {}

    /**
     * Create from API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $response = $data['result']['response'] ?? $data;
        $transaction = $response['transaction'] ?? $data;

        return new self(
            transactionId: (string) ($transaction['transactionId'] ?? ''),
            transactionLink: (string) ($transaction['transactionLink'] ?? ''),
            referenceId: (string) ($response['referenceId'] ?? ''),
        );
    }
}
