<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\Api;

use Core45\TubaPay\Api\OfferApi;
use Core45\TubaPay\DTO\Customer;
use Core45\TubaPay\DTO\Offer;
use Core45\TubaPay\DTO\OrderItem;
use Core45\TubaPay\Enum\Environment;
use Core45\TubaPay\Exception\ValidationException;
use Core45\TubaPay\Http\InMemoryTokenStorage;
use Core45\TubaPay\Http\TubaPayClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OfferApiTest extends TestCase
{
    #[Test]
    public function test_create_offer_returns_offer(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            $this->createOfferResponse(),
        ]);

        $api = $this->createOfferApi($mockHandler);

        $offer = $api->createOffer(
            amount: 1000.0,
            customer: $this->createCustomer(),
            item: $this->createOrderItem(1000.0),
            externalRef: 'ORDER-123',
        );

        $this->assertInstanceOf(Offer::class, $offer);
        $this->assertSame('client', $offer->type);
        $this->assertCount(4, $offer->items);
    }

    #[Test]
    public function test_create_offer_without_external_ref(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            $this->createOfferResponse(),
        ]);

        $api = $this->createOfferApi($mockHandler);

        $offer = $api->createOffer(
            amount: 1000.0,
            customer: $this->createCustomer(),
            item: $this->createOrderItem(1000.0),
        );

        $this->assertInstanceOf(Offer::class, $offer);
    }

    #[Test]
    public function test_create_client_offer_returns_offer(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            $this->createOfferResponse(),
        ]);

        $api = $this->createOfferApi($mockHandler);

        $offer = $api->createClientOffer(1000.0);

        $this->assertInstanceOf(Offer::class, $offer);
        $this->assertSame([3, 6, 9, 12], $offer->getAvailableInstallments());
    }

    #[Test]
    public function test_get_installment_numbers_returns_offer_installments(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            $this->createOfferResponse(),
        ]);

        $api = $this->createOfferApi($mockHandler);

        $this->assertSame([3, 6, 9, 12], $api->getInstallmentNumbers(1000.0));
    }

    #[Test]
    public function test_is_available_for_amount_returns_true_when_installments_exist(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            $this->createOfferResponse(),
        ]);

        $api = $this->createOfferApi($mockHandler);

        $this->assertTrue($api->isAvailableForAmount(1000.0));
    }

    #[Test]
    public function test_create_offer_sends_current_plugin_payload_shape(): void
    {
        $container = [];
        $history = Middleware::history($container);

        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            $this->createOfferResponse(),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($history);

        $httpClient = new Client(['handler' => $handlerStack]);
        $client = new TubaPayClient(
            'client-id',
            'client-secret',
            Environment::Test,
            new InMemoryTokenStorage,
            $httpClient,
        );
        $api = new OfferApi($client);

        $api->createOffer(
            amount: 1000.0,
            customer: $this->createCustomer(),
            item: $this->createOrderItem(1000.0),
            externalRef: 'ORDER-123',
        );

        $this->assertCount(2, $container);
        /** @var Request $request */
        $request = $container[1]['request'];
        $payload = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals([
            'totalValue' => 1000.0,
            'type' => 'client',
        ], $payload);
    }

    #[Test]
    public function test_create_offer_throws_on_zero_amount(): void
    {
        $mockHandler = new MockHandler([]);

        $api = $this->createOfferApi($mockHandler);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Amount must be greater than 0');

        $api->createOffer(
            amount: 0.0,
            customer: $this->createCustomer(),
            item: $this->createOrderItem(0.0),
        );
    }

    #[Test]
    public function test_create_offer_throws_on_negative_amount(): void
    {
        $mockHandler = new MockHandler([]);

        $api = $this->createOfferApi($mockHandler);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Amount must be greater than 0');

        $api->createOffer(
            amount: -100.0,
            customer: $this->createCustomer(),
            item: $this->createOrderItem(-100.0),
        );
    }

    #[Test]
    public function test_create_offer_with_multiple_items(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            $this->createOfferResponse(),
        ]);

        $api = $this->createOfferApi($mockHandler);

        $items = [
            $this->createOrderItem(500.0, 'Product A'),
            $this->createOrderItem(300.0, 'Product B'),
            $this->createOrderItem(200.0, 'Product C'),
        ];

        $offer = $api->createOfferWithItems(
            customer: $this->createCustomer(),
            items: $items,
            externalRef: 'MULTI-ORDER-123',
        );

        $this->assertInstanceOf(Offer::class, $offer);
    }

    #[Test]
    public function test_create_offer_with_items_throws_on_empty_items(): void
    {
        $mockHandler = new MockHandler([]);

        $api = $this->createOfferApi($mockHandler);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('items');

        $api->createOfferWithItems(
            customer: $this->createCustomer(),
            items: [],
        );
    }

    private function createOfferApi(MockHandler $mockHandler): OfferApi
    {
        $httpClient = new Client(['handler' => HandlerStack::create($mockHandler)]);

        $client = new TubaPayClient(
            'client-id',
            'client-secret',
            Environment::Test,
            new InMemoryTokenStorage,
            $httpClient,
        );

        return new OfferApi($client);
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

    private function createOfferResponse(): Response
    {
        return new Response(200, [], json_encode([
            'result' => [
                'response' => [
                    'referenceId' => 'ref-123',
                    'offer' => [
                        'partnerId' => '703419',
                        'accountId' => '703419',
                        'type' => 'client',
                        'totalValue' => 1000,
                        'offerItems' => [
                            ['installmentsNumber' => 3],
                            ['installmentsNumber' => 6],
                            ['installmentsNumber' => 9],
                            ['installmentsNumber' => 12],
                        ],
                        'consents' => [
                            [
                                'type' => 'RODO_BP',
                                'title' => 'Required consent',
                                'optional' => false,
                            ],
                        ],
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
