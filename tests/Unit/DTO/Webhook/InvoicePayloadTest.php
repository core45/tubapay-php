<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\DTO\Webhook;

use Core45\TubaPay\DTO\Webhook\InvoicePayload;
use Core45\TubaPay\DTO\Webhook\InvoicePosition;
use Core45\TubaPay\Enum\AgreementStatus;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoicePayloadTest extends TestCase
{
    #[Test]
    public function test_from_array_parses_complete_payload(): void
    {
        $data = $this->createCompletePayload();

        $payload = InvoicePayload::fromArray($data);

        // Meta data
        $this->assertSame('CUSTOMER_RECURRING_ORDER_REQUEST', $payload->commandType);
        $this->assertSame('inv-cmd-123', $payload->commandRef);
        $this->assertInstanceOf(DateTimeImmutable::class, $payload->commandDateTime);
        $this->assertSame('https://example.com/webhook', $payload->commandCallbackUrl);

        // Partner
        $this->assertSame('703419', $payload->tubapayPartnerId);
        $this->assertSame('Test Partner', $payload->partnerName);

        // Transaction
        $this->assertSame(AgreementStatus::Accepted, $payload->agreementStatus);
        $this->assertSame(3000.0, $payload->agreementNetValue);
        $this->assertSame('BACCA_PAY', $payload->originCompany);
        $this->assertSame('ORDER-INV-123', $payload->externalRef);
        $this->assertSame('AGR-INV-456', $payload->agreementNumber);

        // Invoice specific fields
        $this->assertSame(333.33, $payload->requestTotalAmount);
        $this->assertCount(1, $payload->requestPositions);
        $this->assertInstanceOf(InvoicePosition::class, $payload->requestPositions[0]);

        // Customer
        $this->assertSame('Jan', $payload->customer->firstName);
        $this->assertSame('Kowalski', $payload->customer->lastName);

        // Items
        $this->assertCount(1, $payload->items);
    }

    #[Test]
    public function test_from_array_parses_multiple_positions(): void
    {
        $data = [
            'metaData' => ['commandType' => 'CUSTOMER_RECURRING_ORDER_REQUEST'],
            'payload' => [
                'transaction' => [
                    'agreementStatus' => 'accepted',
                    'requestTotalAmount' => 666.66,
                    'requestPositions' => [
                        ['rateNumber' => 1, 'totalAmount' => 333.33, 'paymentScheduleId' => 'PS-001'],
                        ['rateNumber' => 2, 'totalAmount' => 333.33, 'paymentScheduleId' => 'PS-002'],
                    ],
                ],
            ],
        ];

        $payload = InvoicePayload::fromArray($data);

        $this->assertCount(2, $payload->requestPositions);
        $this->assertSame(1, $payload->requestPositions[0]->rateNumber);
        $this->assertSame(2, $payload->requestPositions[1]->rateNumber);
        $this->assertSame(333.33, $payload->requestPositions[0]->totalAmount);
        $this->assertSame('PS-001', $payload->requestPositions[0]->paymentScheduleId);
        $this->assertSame('PS-002', $payload->requestPositions[1]->paymentScheduleId);
    }

    #[Test]
    public function test_get_first_position_returns_first(): void
    {
        $data = [
            'metaData' => ['commandType' => 'CUSTOMER_RECURRING_ORDER_REQUEST'],
            'payload' => [
                'transaction' => [
                    'agreementStatus' => 'accepted',
                    'requestPositions' => [
                        ['rateNumber' => 3, 'totalAmount' => 100.0],
                        ['rateNumber' => 4, 'totalAmount' => 200.0],
                    ],
                ],
            ],
        ];

        $payload = InvoicePayload::fromArray($data);
        $firstPosition = $payload->getFirstPosition();

        $this->assertNotNull($firstPosition);
        $this->assertSame(3, $firstPosition->rateNumber);
        $this->assertSame(100.0, $firstPosition->totalAmount);
    }

    #[Test]
    public function test_get_first_position_returns_null_when_empty(): void
    {
        $data = [
            'metaData' => ['commandType' => 'CUSTOMER_RECURRING_ORDER_REQUEST'],
            'payload' => [
                'transaction' => [
                    'agreementStatus' => 'accepted',
                    'requestPositions' => [],
                ],
            ],
        ];

        $payload = InvoicePayload::fromArray($data);

        $this->assertNull($payload->getFirstPosition());
    }

    #[Test]
    public function test_from_array_handles_empty_payload(): void
    {
        $data = [
            'metaData' => ['commandType' => 'CUSTOMER_RECURRING_ORDER_REQUEST'],
        ];

        $payload = InvoicePayload::fromArray($data);

        $this->assertSame('CUSTOMER_RECURRING_ORDER_REQUEST', $payload->commandType);
        $this->assertSame(AgreementStatus::Draft, $payload->agreementStatus);
        $this->assertSame(0.0, $payload->requestTotalAmount);
        $this->assertCount(0, $payload->requestPositions);
        $this->assertCount(0, $payload->items);
    }

    #[Test]
    public function test_is_invoice_request_returns_true(): void
    {
        $data = $this->createCompletePayload();
        $payload = InvoicePayload::fromArray($data);

        $this->assertTrue($payload->isInvoiceRequest());
        $this->assertFalse($payload->isStatusChanged());
        $this->assertFalse($payload->isPaymentNotification());
    }

    #[Test]
    public function test_invoice_position_from_array(): void
    {
        $data = [
            'rateNumber' => 5,
            'totalAmount' => 199.99,
            'paymentScheduleId' => 'SCHEDULE-123',
        ];

        $position = InvoicePosition::fromArray($data);

        $this->assertSame(5, $position->rateNumber);
        $this->assertSame(199.99, $position->totalAmount);
        $this->assertSame('SCHEDULE-123', $position->paymentScheduleId);
    }

    #[Test]
    public function test_invoice_position_from_array_handles_missing_fields(): void
    {
        $position = InvoicePosition::fromArray([]);

        $this->assertSame(0, $position->rateNumber);
        $this->assertSame(0.0, $position->totalAmount);
        $this->assertNull($position->paymentScheduleId);
    }

    #[Test]
    public function test_invoice_position_constructor(): void
    {
        $position = new InvoicePosition(
            rateNumber: 3,
            totalAmount: 250.50,
            paymentScheduleId: 'PS-789',
        );

        $this->assertSame(3, $position->rateNumber);
        $this->assertSame(250.50, $position->totalAmount);
        $this->assertSame('PS-789', $position->paymentScheduleId);
    }

    #[Test]
    public function test_invoice_position_optional_payment_schedule_id(): void
    {
        $position = new InvoicePosition(
            rateNumber: 1,
            totalAmount: 100.0,
        );

        $this->assertSame(1, $position->rateNumber);
        $this->assertNull($position->paymentScheduleId);
    }

    /**
     * @return array<string, mixed>
     */
    private function createCompletePayload(): array
    {
        return [
            'metaData' => [
                'commandType' => 'CUSTOMER_RECURRING_ORDER_REQUEST',
                'commandRef' => 'inv-cmd-123',
                'commandDateTime' => '2024-02-01T09:00:00Z',
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
                    'agreementNetValue' => 3000.0,
                    'originCompany' => 'BACCA_PAY',
                    'templateName' => 'invoice_template',
                    'templateFileVersion' => '1.0',
                    'externalRef' => 'ORDER-INV-123',
                    'agreementNumber' => 'AGR-INV-456',
                    'requestTotalAmount' => 333.33,
                    'requestPositions' => [
                        ['rateNumber' => 1, 'totalAmount' => 333.33, 'paymentScheduleId' => 'PS-001'],
                    ],
                    'items' => [
                        ['name' => 'Subscription Product', 'totalValue' => 3000.0],
                    ],
                ],
            ],
        ];
    }
}
