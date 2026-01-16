<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\DTO\Webhook;

use Core45\TubaPay\DTO\Webhook\StatusChangedPayload;
use Core45\TubaPay\Enum\AgreementStatus;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StatusChangedPayloadTest extends TestCase
{
    #[Test]
    public function test_from_array_parses_complete_payload(): void
    {
        $data = $this->createCompletePayload();

        $payload = StatusChangedPayload::fromArray($data);

        // Meta data
        $this->assertSame('TRANSACTION_STATUS_CHANGED', $payload->commandType);
        $this->assertSame('cmd-ref-123', $payload->commandRef);
        $this->assertInstanceOf(DateTimeImmutable::class, $payload->commandDateTime);
        $this->assertSame('https://example.com/webhook', $payload->commandCallbackUrl);
        $this->assertSame('custom', $payload->commandCallbackType);

        // Partner
        $this->assertSame('703419', $payload->tubapayPartnerId);
        $this->assertSame('Test Partner', $payload->partnerName);

        // Transaction
        $this->assertSame(AgreementStatus::Accepted, $payload->agreementStatus);
        $this->assertSame(1500.0, $payload->agreementNetValue);
        $this->assertSame('BACCA_PAY', $payload->originCompany);
        $this->assertSame('test_template', $payload->templateName);
        $this->assertSame('2.1', $payload->templateFileVersion);
        $this->assertSame('ORDER-456', $payload->externalRef);
        $this->assertSame('AGR-789', $payload->agreementNumber);

        // Customer
        $this->assertSame('Jan', $payload->customer->firstName);
        $this->assertSame('Kowalski', $payload->customer->lastName);
        $this->assertSame('jan@example.com', $payload->customer->email);

        // Items
        $this->assertCount(1, $payload->items);
        $this->assertSame('Test Product', $payload->items[0]->name);
        $this->assertSame(1500.0, $payload->items[0]->totalValue);

        // Raw payload preserved
        $this->assertSame($data, $payload->rawPayload);
    }

    #[Test]
    public function test_from_array_parses_datetime_array_format(): void
    {
        $data = $this->createPayloadWithDateTimeArray();

        $payload = StatusChangedPayload::fromArray($data);

        $this->assertInstanceOf(DateTimeImmutable::class, $payload->commandDateTime);
        $this->assertSame('2024-01-15 10:30:45', $payload->commandDateTime->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function test_from_array_parses_datetime_string_format(): void
    {
        $data = $this->createPayloadWithDateTimeString();

        $payload = StatusChangedPayload::fromArray($data);

        $this->assertInstanceOf(DateTimeImmutable::class, $payload->commandDateTime);
        $this->assertSame('2024-01-15', $payload->commandDateTime->format('Y-m-d'));
    }

    #[Test]
    public function test_from_array_handles_null_datetime(): void
    {
        $data = $this->createMinimalPayload();

        $payload = StatusChangedPayload::fromArray($data);

        $this->assertNull($payload->commandDateTime);
    }

    #[Test]
    public function test_from_array_handles_empty_payload(): void
    {
        $data = [
            'metaData' => ['commandType' => 'TRANSACTION_STATUS_CHANGED'],
        ];

        $payload = StatusChangedPayload::fromArray($data);

        $this->assertSame('', $payload->commandRef);
        $this->assertSame('', $payload->tubapayPartnerId);
        $this->assertSame(AgreementStatus::Draft, $payload->agreementStatus);
        $this->assertSame(0.0, $payload->agreementNetValue);
        $this->assertNull($payload->externalRef);
        $this->assertNull($payload->agreementNumber);
        $this->assertCount(0, $payload->items);
    }

    #[Test]
    public function test_is_accepted(): void
    {
        $acceptedPayload = $this->createPayloadWithStatus('accepted');
        $rejectedPayload = $this->createPayloadWithStatus('rejected');

        $this->assertTrue(StatusChangedPayload::fromArray($acceptedPayload)->isAccepted());
        $this->assertFalse(StatusChangedPayload::fromArray($rejectedPayload)->isAccepted());
    }

    #[Test]
    public function test_is_rejected(): void
    {
        $rejectedPayload = $this->createPayloadWithStatus('rejected');
        $acceptedPayload = $this->createPayloadWithStatus('accepted');

        $this->assertTrue(StatusChangedPayload::fromArray($rejectedPayload)->isRejected());
        $this->assertFalse(StatusChangedPayload::fromArray($acceptedPayload)->isRejected());
    }

    #[Test]
    public function test_is_final_status(): void
    {
        $closedPayload = $this->createPayloadWithStatus('closed');
        $rejectedPayload = $this->createPayloadWithStatus('rejected');
        $acceptedPayload = $this->createPayloadWithStatus('accepted');
        $draftPayload = $this->createPayloadWithStatus('draft');

        $this->assertTrue(StatusChangedPayload::fromArray($closedPayload)->isFinalStatus());
        $this->assertTrue(StatusChangedPayload::fromArray($rejectedPayload)->isFinalStatus());
        $this->assertFalse(StatusChangedPayload::fromArray($acceptedPayload)->isFinalStatus());
        $this->assertFalse(StatusChangedPayload::fromArray($draftPayload)->isFinalStatus());
    }

    #[Test]
    public function test_parses_multiple_items(): void
    {
        $data = [
            'metaData' => ['commandType' => 'TRANSACTION_STATUS_CHANGED'],
            'payload' => [
                'transaction' => [
                    'agreementStatus' => 'accepted',
                    'items' => [
                        ['name' => 'Product A', 'totalValue' => 500.0],
                        ['name' => 'Product B', 'totalValue' => 300.0],
                        ['name' => 'Product C', 'totalValue' => 200.0],
                    ],
                ],
            ],
        ];

        $payload = StatusChangedPayload::fromArray($data);

        $this->assertCount(3, $payload->items);
        $this->assertSame('Product A', $payload->items[0]->name);
        $this->assertSame('Product B', $payload->items[1]->name);
        $this->assertSame('Product C', $payload->items[2]->name);
    }

    /**
     * @return array<string, mixed>
     */
    private function createCompletePayload(): array
    {
        return [
            'metaData' => [
                'commandType' => 'TRANSACTION_STATUS_CHANGED',
                'commandRef' => 'cmd-ref-123',
                'commandDateTime' => '2024-01-15T10:30:00Z',
                'commandCallbackUrl' => 'https://example.com/webhook',
                'commandCallbackType' => 'custom',
            ],
            'payload' => [
                'partner' => [
                    'tubapayPartnerId' => '703419',
                    'partnerName' => 'Test Partner',
                ],
                'customer' => [
                    'firstName' => 'Jan',
                    'lastName' => 'Kowalski',
                    'email' => 'jan@example.com',
                    'phone' => '519088975',
                    'street' => 'Testowa',
                    'zipCode' => '00-001',
                    'town' => 'Warszawa',
                ],
                'transaction' => [
                    'agreementStatus' => 'accepted',
                    'agreementNetValue' => 1500.0,
                    'originCompany' => 'BACCA_PAY',
                    'templateName' => 'test_template',
                    'templateFileVersion' => '2.1',
                    'externalRef' => 'ORDER-456',
                    'agreementNumber' => 'AGR-789',
                    'items' => [
                        ['name' => 'Test Product', 'totalValue' => 1500.0],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createPayloadWithDateTimeArray(): array
    {
        return [
            'metaData' => [
                'commandType' => 'TRANSACTION_STATUS_CHANGED',
                'commandDateTime' => [2024, 1, 15, 10, 30, 45, 0],
            ],
            'payload' => [
                'transaction' => ['agreementStatus' => 'draft'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createPayloadWithDateTimeString(): array
    {
        return [
            'metaData' => [
                'commandType' => 'TRANSACTION_STATUS_CHANGED',
                'commandDateTime' => '2024-01-15',
            ],
            'payload' => [
                'transaction' => ['agreementStatus' => 'draft'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createMinimalPayload(): array
    {
        return [
            'metaData' => [
                'commandType' => 'TRANSACTION_STATUS_CHANGED',
            ],
            'payload' => [
                'transaction' => ['agreementStatus' => 'draft'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createPayloadWithStatus(string $status): array
    {
        return [
            'metaData' => ['commandType' => 'TRANSACTION_STATUS_CHANGED'],
            'payload' => [
                'transaction' => ['agreementStatus' => $status],
            ],
        ];
    }
}
