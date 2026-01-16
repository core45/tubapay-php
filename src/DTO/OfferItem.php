<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO;

/**
 * Single installment option from a TubaPay offer.
 *
 * For "client" type offers, only installmentsNumber is returned.
 * For "partner" type offers, additional financial details are included.
 */
readonly class OfferItem
{
    public function __construct(
        /** Number of installments (e.g., 3, 6, 9, 12). */
        public int $installmentsNumber,
        /** Technical product ID (partner type only). */
        public ?string $productId = null,
        /** Annual percentage rate (partner type only). */
        public ?float $rrsoPercent = null,
        /** Price multiplier for user display (partner type only). */
        public ?float $userPriceMultiplier = null,
        /** Company handling the agreement (partner type only). */
        public ?string $originCompany = null,
        /** Minimum transaction amount (partner type only). */
        public ?float $minNetAmount = null,
        /** Maximum transaction amount (partner type only). */
        public ?float $maxNetAmount = null,
        /** Monthly provision percentage (partner type only). */
        public ?float $provisionPercent = null,
        /** Payment type to partner (partner type only). */
        public ?string $payoffType = null,
    ) {}

    /**
     * Create from API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            installmentsNumber: (int) ($data['installmentsNumber'] ?? 0),
            productId: isset($data['productId']) ? (string) $data['productId'] : null,
            rrsoPercent: isset($data['rrsoPercent']) ? (float) $data['rrsoPercent'] : null,
            userPriceMultiplier: isset($data['userPriceMultiplier']) ? (float) $data['userPriceMultiplier'] : null,
            originCompany: isset($data['originCompany']) ? (string) $data['originCompany'] : null,
            minNetAmount: isset($data['minNetAmount']) ? (float) $data['minNetAmount'] : null,
            maxNetAmount: isset($data['maxNetAmount']) ? (float) $data['maxNetAmount'] : null,
            provisionPercent: isset($data['provisionPercent']) ? (float) $data['provisionPercent'] : null,
            payoffType: isset($data['payoffType']) ? (string) $data['payoffType'] : null,
        );
    }

    /**
     * Check if this is a detailed partner-type offer item.
     */
    public function isDetailedOffer(): bool
    {
        return $this->productId !== null;
    }

    /**
     * Check if a given amount is within the allowed range for this offer.
     */
    public function isAmountInRange(float $amount): bool
    {
        if ($this->minNetAmount === null || $this->maxNetAmount === null) {
            return true;
        }

        return $amount >= $this->minNetAmount && $amount <= $this->maxNetAmount;
    }
}
