<?php

declare(strict_types=1);

namespace Core45\TubaPay\Api;

use Core45\TubaPay\DTO\Customer;
use Core45\TubaPay\DTO\Offer;
use Core45\TubaPay\DTO\OrderItem;
use Core45\TubaPay\Exception\ApiException;
use Core45\TubaPay\Exception\AuthenticationException;
use Core45\TubaPay\Exception\ValidationException;
use Core45\TubaPay\Http\TubaPayClient;

/**
 * API for creating and managing TubaPay offers.
 *
 * An offer represents available installment options for a given amount.
 */
final class OfferApi
{
    private const CREATE_OFFER_PATH = '/api/v1/external/transaction/create-offer';

    public function __construct(
        private readonly TubaPayClient $client,
    ) {}

    /**
     * Create an offer to get available installment options.
     *
     * @param  float  $amount  Total order amount (must be within partner's configured limits)
     * @param  Customer  $customer  Customer details
     * @param  OrderItem  $item  Order item details
     * @param  string|null  $externalRef  Your order reference ID (optional but recommended)
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function createOffer(
        float $amount,
        Customer $customer,
        OrderItem $item,
        ?string $externalRef = null,
    ): Offer {
        $this->validateAmount($amount);

        $payload = $this->buildClientOfferPayload($amount);

        $response = $this->client->post(self::CREATE_OFFER_PATH, $payload);

        return Offer::fromArray($response);
    }

    /**
     * Create an offer with multiple items.
     *
     * @param  Customer  $customer  Customer details
     * @param  list<OrderItem>  $items  Order items
     * @param  string|null  $externalRef  Your order reference ID
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function createOfferWithItems(
        Customer $customer,
        array $items,
        ?string $externalRef = null,
    ): Offer {
        if (count($items) === 0) {
            throw ValidationException::missingField('items');
        }

        $totalAmount = array_sum(array_map(
            static fn (OrderItem $item): float => $item->totalValue,
            $items
        ));

        $this->validateAmount($totalAmount);

        $payload = $this->buildClientOfferPayload($totalAmount);

        $response = $this->client->post(self::CREATE_OFFER_PATH, $payload);

        return Offer::fromArray($response);
    }

    /**
     * @throws ValidationException
     */
    private function validateAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::invalidField('amount', 'Amount must be greater than 0.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildClientOfferPayload(float $amount): array
    {
        return [
            'totalValue' => $amount,
            'type' => 'client',
        ];
    }
}
