<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO\Webhook;

/**
 * Invoice request position (installment to be invoiced).
 */
readonly class InvoicePosition
{
    public function __construct(
        /** Installment/rate number. */
        public int $rateNumber,
        /** Amount for this installment. */
        public float $totalAmount,
        /** Payment schedule ID (optional). */
        public ?string $paymentScheduleId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            rateNumber: (int) ($data['rateNumber'] ?? 0),
            totalAmount: (float) ($data['totalAmount'] ?? 0.0),
            paymentScheduleId: isset($data['paymentScheduleId']) ? (string) $data['paymentScheduleId'] : null,
        );
    }
}
