<?php

declare(strict_types=1);

namespace Core45\TubaPay\Api;

use Core45\TubaPay\DTO\Customer;
use Core45\TubaPay\DTO\OrderItem;
use Core45\TubaPay\DTO\Transaction;
use Core45\TubaPay\Exception\ApiException;
use Core45\TubaPay\Exception\AuthenticationException;
use Core45\TubaPay\Exception\ValidationException;
use Core45\TubaPay\Http\TubaPayClient;

/**
 * API for creating TubaPay transactions.
 *
 * A transaction initiates the actual payment process with a selected installment plan.
 */
final class TransactionApi
{
    private const CREATE_TRANSACTION_PATH = '/api/v1/external/transaction/create';

    public function __construct(
        private readonly TubaPayClient $client,
    ) {}

    /**
     * Create a transaction with a specific installment plan.
     *
     * @param  Customer  $customer  Customer details
     * @param  OrderItem  $item  Order item
     * @param  int  $installments  Number of installments (must be from offer)
     * @param  string  $callbackUrl  URL for webhook notifications
     * @param  string|null  $externalRef  Your order reference ID
     * @param  string|null  $productId  Specific product ID from partner offer (optional)
     * @param  string|null  $returnUrl  URL to redirect customer after payment completion
     * @param  list<string>  $acceptedConsents  Accepted consent identifiers
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function createTransaction(
        Customer $customer,
        OrderItem $item,
        int $installments,
        string $callbackUrl,
        ?string $externalRef = null,
        ?string $productId = null,
        ?string $returnUrl = null,
        array $acceptedConsents = [],
    ): Transaction {
        $this->validateInstallments($installments);
        $this->validateCallbackUrl($callbackUrl);

        $payload = $this->buildPayload(
            $customer,
            [$item],
            $installments,
            $callbackUrl,
            $externalRef,
            $productId,
            $returnUrl,
            $acceptedConsents
        );

        $response = $this->client->post(self::CREATE_TRANSACTION_PATH, $payload);

        return Transaction::fromArray($response);
    }

    /**
     * Create a transaction with multiple items.
     *
     * @param  Customer  $customer  Customer details
     * @param  list<OrderItem>  $items  Order items
     * @param  int  $installments  Number of installments
     * @param  string  $callbackUrl  URL for webhook notifications
     * @param  string|null  $externalRef  Your order reference ID
     * @param  string|null  $productId  Specific product ID from partner offer
     * @param  string|null  $returnUrl  URL to redirect customer after payment completion
     * @param  list<string>  $acceptedConsents  Accepted consent identifiers
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function createTransactionWithItems(
        Customer $customer,
        array $items,
        int $installments,
        string $callbackUrl,
        ?string $externalRef = null,
        ?string $productId = null,
        ?string $returnUrl = null,
        array $acceptedConsents = [],
    ): Transaction {
        if (count($items) === 0) {
            throw ValidationException::missingField('items');
        }

        $this->validateInstallments($installments);
        $this->validateCallbackUrl($callbackUrl);

        $payload = $this->buildPayload(
            $customer,
            $items,
            $installments,
            $callbackUrl,
            $externalRef,
            $productId,
            $returnUrl,
            $acceptedConsents
        );

        $response = $this->client->post(self::CREATE_TRANSACTION_PATH, $payload);

        return Transaction::fromArray($response);
    }

    /**
     * @param  list<OrderItem>  $items
     * @param  list<string>  $acceptedConsents
     * @return array<string, mixed>
     */
    private function buildPayload(
        Customer $customer,
        array $items,
        int $installments,
        string $callbackUrl,
        ?string $externalRef,
        ?string $productId,
        ?string $returnUrl,
        array $acceptedConsents,
    ): array {
        $payload = [
            'customer' => $customer->toArray(),
            'order' => [
                'callbackUrl' => $callbackUrl,
                'acceptedConsents' => $acceptedConsents,
            ],
            'offer' => [
                'installmentsNumber' => $installments,
            ],
        ];

        if (count($items) === 1) {
            $payload['order']['item'] = $items[0]->toArray();
        } else {
            $payload['order']['items'] = array_map(
                static fn (OrderItem $item): array => $item->toArray(),
                $items
            );
        }

        if ($externalRef !== null) {
            $payload['order']['externalRef'] = $externalRef;
        }

        if ($productId !== null) {
            $payload['offer']['productId'] = $productId;
        }

        if ($returnUrl !== null) {
            $payload['order']['returnUrl'] = $returnUrl;
        }

        return $payload;
    }

    /**
     * @throws ValidationException
     */
    private function validateInstallments(int $installments): void
    {
        if ($installments < 1) {
            throw ValidationException::invalidField(
                'installments',
                'Installments must be at least 1.'
            );
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateCallbackUrl(string $callbackUrl): void
    {
        if (empty($callbackUrl)) {
            throw ValidationException::missingField('callbackUrl');
        }

        if (! filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            throw ValidationException::invalidField(
                'callbackUrl',
                'Must be a valid URL.'
            );
        }

        if (! str_starts_with($callbackUrl, 'https://')) {
            throw ValidationException::invalidField(
                'callbackUrl',
                'Must use HTTPS protocol.'
            );
        }
    }
}
