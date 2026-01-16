<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO;

/**
 * TubaPay offer response containing available installment options.
 */
readonly class Offer
{
    /**
     * @param list<OfferItem> $items Available installment options
     */
    public function __construct(
        public string $referenceId,
        public string $partnerId,
        public string $accountId,
        public string $type,
        public float $totalValue,
        public array $items,
    ) {}

    /**
     * Create from API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $response = $data['result']['response'] ?? $data;
        $offer = $response['offer'] ?? $data;

        $items = [];
        foreach (($offer['offerItems'] ?? []) as $itemData) {
            if (is_array($itemData)) {
                $items[] = OfferItem::fromArray($itemData);
            }
        }

        return new self(
            referenceId: (string) ($response['referenceId'] ?? ''),
            partnerId: (string) ($offer['partnerId'] ?? ''),
            accountId: (string) ($offer['accountId'] ?? ''),
            type: (string) ($offer['type'] ?? 'client'),
            totalValue: (float) ($offer['totalValue'] ?? 0.0),
            items: $items,
        );
    }

    /**
     * Get available installment numbers.
     *
     * @return list<int>
     */
    public function getAvailableInstallments(): array
    {
        return array_map(
            fn (OfferItem $item) => $item->installmentsNumber,
            $this->items
        );
    }

    /**
     * Find an offer item by installment number.
     */
    public function findByInstallments(int $installmentsNumber): ?OfferItem
    {
        foreach ($this->items as $item) {
            if ($item->installmentsNumber === $installmentsNumber) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Check if a specific installment option is available.
     */
    public function hasInstallmentOption(int $installmentsNumber): bool
    {
        return $this->findByInstallments($installmentsNumber) !== null;
    }

    /**
     * Check if this is a client-type offer (simplified).
     */
    public function isClientOffer(): bool
    {
        return $this->type === 'client';
    }

    /**
     * Check if this is a partner-type offer (detailed).
     */
    public function isPartnerOffer(): bool
    {
        return $this->type === 'partner';
    }
}
