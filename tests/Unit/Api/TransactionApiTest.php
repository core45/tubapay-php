<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\Api;

use Core45\TubaPay\Api\TransactionApi;
use Core45\TubaPay\DTO\Customer;
use Core45\TubaPay\DTO\OrderItem;
use Core45\TubaPay\DTO\Transaction;
use Core45\TubaPay\Enum\Environment;
use Core45\TubaPay\Exception\ValidationException;
use Core45\TubaPay\Http\InMemoryTokenStorage;
use Core45\TubaPay\Http\TubaPayClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransactionApiTest extends TestCase
{
    #[Test]
    public function test_create_transaction_returns_transaction(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            $this->createTransactionResponse(),
        ]);

        $api = $this->createTransactionApi($mockHandler);

        $transaction = $api->createTransaction(
            customer: $this->createCustomer(),
            item: $this->createOrderItem(1000.0),
            installments: 6,
            callbackUrl: 'https://example.com/webhook',
            externalRef: 'ORDER-123',
        );

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertSame('aWuiqgk9pWhT65VNvkx', $transaction->transactionId);
        $this->assertStringStartsWith('https://', $transaction->transactionLink);
    }

    #[Test]
    public function test_create_transaction_with_product_id(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            $this->createTransactionResponse(),
        ]);

        $api = $this->createTransactionApi($mockHandler);

        $transaction = $api->createTransaction(
            customer: $this->createCustomer(),
            item: $this->createOrderItem(1000.0),
            installments: 6,
            callbackUrl: 'https://example.com/webhook',
            externalRef: 'ORDER-123',
            productId: '6rat_ecomm-abon_c0%_m11.34%_bp',
        );

        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    #[Test]
    public function test_create_transaction_throws_on_invalid_installments(): void
    {
        $mockHandler = new MockHandler([]);

        $api = $this->createTransactionApi($mockHandler);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid installments');

        $api->createTransaction(
            customer: $this->createCustomer(),
            item: $this->createOrderItem(1000.0),
            installments: 7, // Invalid - not in allowed list
            callbackUrl: 'https://example.com/webhook',
        );
    }

    #[Test]
    public function test_create_transaction_throws_on_zero_installments(): void
    {
        $mockHandler = new MockHandler([]);

        $api = $this->createTransactionApi($mockHandler);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('at least 1');

        $api->createTransaction(
            customer: $this->createCustomer(),
            item: $this->createOrderItem(1000.0),
            installments: 0,
            callbackUrl: 'https://example.com/webhook',
        );
    }

    #[Test]
    public function test_create_transaction_throws_on_missing_callback_url(): void
    {
        $mockHandler = new MockHandler([]);

        $api = $this->createTransactionApi($mockHandler);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('callbackUrl');

        $api->createTransaction(
            customer: $this->createCustomer(),
            item: $this->createOrderItem(1000.0),
            installments: 6,
            callbackUrl: '',
        );
    }

    #[Test]
    public function test_create_transaction_throws_on_invalid_callback_url(): void
    {
        $mockHandler = new MockHandler([]);

        $api = $this->createTransactionApi($mockHandler);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('valid URL');

        $api->createTransaction(
            customer: $this->createCustomer(),
            item: $this->createOrderItem(1000.0),
            installments: 6,
            callbackUrl: 'not-a-url',
        );
    }

    #[Test]
    public function test_create_transaction_throws_on_http_callback_url(): void
    {
        $mockHandler = new MockHandler([]);

        $api = $this->createTransactionApi($mockHandler);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('HTTPS');

        $api->createTransaction(
            customer: $this->createCustomer(),
            item: $this->createOrderItem(1000.0),
            installments: 6,
            callbackUrl: 'http://example.com/webhook', // Not HTTPS
        );
    }

    #[Test]
    public function test_create_transaction_with_multiple_items(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            $this->createTransactionResponse(),
        ]);

        $api = $this->createTransactionApi($mockHandler);

        $items = [
            $this->createOrderItem(500.0, 'Product A'),
            $this->createOrderItem(500.0, 'Product B'),
        ];

        $transaction = $api->createTransactionWithItems(
            customer: $this->createCustomer(),
            items: $items,
            installments: 6,
            callbackUrl: 'https://example.com/webhook',
            externalRef: 'MULTI-ORDER-123',
        );

        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    #[Test]
    public function test_create_transaction_with_empty_items_throws(): void
    {
        $mockHandler = new MockHandler([]);

        $api = $this->createTransactionApi($mockHandler);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('items');

        $api->createTransactionWithItems(
            customer: $this->createCustomer(),
            items: [],
            installments: 6,
            callbackUrl: 'https://example.com/webhook',
        );
    }

    #[Test]
    public function test_valid_installment_numbers(): void
    {
        $validInstallments = [1, 3, 6, 9, 10, 12, 18, 24, 36, 48];

        foreach ($validInstallments as $installments) {
            $mockHandler = new MockHandler([
                $this->createTokenResponse(),
                $this->createTransactionResponse(),
            ]);

            $api = $this->createTransactionApi($mockHandler);

            $transaction = $api->createTransaction(
                customer: $this->createCustomer(),
                item: $this->createOrderItem(1000.0),
                installments: $installments,
                callbackUrl: 'https://example.com/webhook',
            );

            $this->assertInstanceOf(Transaction::class, $transaction);
        }
    }

    private function createTransactionApi(MockHandler $mockHandler): TransactionApi
    {
        $httpClient = new Client(['handler' => HandlerStack::create($mockHandler)]);

        $client = new TubaPayClient(
            'client-id',
            'client-secret',
            Environment::Test,
            new InMemoryTokenStorage(),
            $httpClient,
        );

        return new TransactionApi($client);
    }

    private function createTokenResponse(): Response
    {
        return new Response(200, [], json_encode([
            'result' => [
                'response' => [
                    'accessToken' => 'test-token',
                    'expiresIn' => 86400,
                ],
            ],
        ], JSON_THROW_ON_ERROR));
    }

    private function createTransactionResponse(): Response
    {
        return new Response(200, [], json_encode([
            'result' => [
                'response' => [
                    'referenceId' => 'f1960ba5-3154-4bfe-824b-ffe9e2e85f66',
                    'transaction' => [
                        'transactionLink' => 'https://tubapay-test.bacca.pl/s/BveALF0R',
                        'transactionId' => 'aWuiqgk9pWhT65VNvkx',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));
    }

    private function createCustomer(): Customer
    {
        return new Customer(
            firstName: 'Jan',
            lastName: 'Kowalski',
            email: 'jan@example.com',
            phone: '519088975',
            street: 'Testowa',
            zipCode: '00-001',
            town: 'Warszawa',
        );
    }

    private function createOrderItem(float $value, string $name = 'Test Product'): OrderItem
    {
        return new OrderItem(
            name: $name,
            totalValue: $value,
        );
    }
}
