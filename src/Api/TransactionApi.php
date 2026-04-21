<?php

declare(strict_types=1);

namespace Core45\TubaPay\Api;

use Core45\TubaPay\DTO\CheckoutSelection;
use Core45\TubaPay\DTO\Customer;
use Core45\TubaPay\DTO\OrderItem;
use Core45\TubaPay\DTO\Transaction;
use Core45\TubaPay\DTO\TransactionMetadata;
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
     * @param  TransactionMetadata|null  $metadata  Integration metadata
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
        ?TransactionMetadata $metadata = null,
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
            $acceptedConsents,
            $metadata
        );

        $response = $this->client->post(self::CREATE_TRANSACTION_PATH, $payload);

        return Transaction::fromArray($response);
    }

    /**
     * Create a transaction with a list of items.
     *
     * TubaPay's `/api/v1/external/transaction/create` endpoint accepts exactly one
     * `order.item` with a single `totalValue` — the reference WordPress integration
     * (tubapay-v2) sends `$order->get_total()` as that value. This method therefore
     * rejects lists longer than one element; callers with multiple order rows must
     * aggregate them (sum `totalValue`s and choose a representative name such as
     * "Zamówienie nr {id}") before calling.
     *
     * @param  Customer  $customer  Customer details
     * @param  list<OrderItem>  $items  Must contain exactly one item
     * @param  int  $installments  Number of installments
     * @param  string  $callbackUrl  URL for webhook notifications
     * @param  string|null  $externalRef  Your order reference ID
     * @param  string|null  $productId  Specific product ID from partner offer
     * @param  string|null  $returnUrl  URL to redirect customer after payment completion
     * @param  list<string>  $acceptedConsents  Accepted consent identifiers
     * @param  TransactionMetadata|null  $metadata  Integration metadata
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
        ?TransactionMetadata $metadata = null,
    ): Transaction {
        if (count($items) === 0) {
            throw ValidationException::missingField('items');
        }

        if (count($items) > 1) {
            throw ValidationException::invalidField(
                'items',
                'TubaPay accepts exactly one order.item per transaction; aggregate multiple rows (sum totalValue, use a representative name like "Zamówienie nr {id}") before calling.'
            );
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
            $acceptedConsents,
            $metadata
        );

        $response = $this->client->post(self::CREATE_TRANSACTION_PATH, $payload);

        return Transaction::fromArray($response);
    }

    /**
     * Create a transaction from a checkout selection DTO.
     *
     * @param  Customer  $customer  Customer details
     * @param  list<OrderItem>  $items  Must contain exactly one item (see createTransactionWithItems)
     * @param  string  $callbackUrl  URL for webhook notifications
     * @param  CheckoutSelection  $selection  User checkout choices
     * @param  string|null  $externalRef  Your order reference ID
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function createTransactionFromSelection(
        Customer $customer,
        array $items,
        string $callbackUrl,
        CheckoutSelection $selection,
        ?string $externalRef = null,
    ): Transaction {
        return $this->createTransactionWithItems(
            customer: $customer,
            items: $items,
            installments: $selection->installments,
            callbackUrl: $callbackUrl,
            externalRef: $externalRef,
            productId: null,
            returnUrl: $selection->returnUrl,
            acceptedConsents: $selection->acceptedConsents,
            metadata: $selection->metadata,
        );
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
        ?TransactionMetadata $metadata,
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

        // Enforced by createTransaction()/createTransactionWithItems(): exactly one item.
        $payload['order']['item'] = $items[0]->toArray();

        if ($externalRef !== null) {
            $payload['order']['externalRef'] = $externalRef;
        }

        if ($productId !== null) {
            $payload['offer']['productId'] = $productId;
        }

        if ($returnUrl !== null) {
            $payload['order']['returnUrl'] = $returnUrl;
        }

        if ($metadata !== null) {
            $payload['order'] = array_merge($payload['order'], $metadata->toArray());
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
