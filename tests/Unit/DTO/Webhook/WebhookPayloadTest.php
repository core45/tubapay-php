<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\DTO\Webhook;

use Core45\TubaPay\DTO\Webhook\InvoicePayload;
use Core45\TubaPay\DTO\Webhook\PaymentPayload;
use Core45\TubaPay\DTO\Webhook\StatusChangedPayload;
use Core45\TubaPay\DTO\Webhook\WebhookPayload;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WebhookPayloadTest extends TestCase
{
    #[Test]
    public function test_from_array_creates_status_changed_payload(): void
    {
        $data = $this->createStatusChangedData();

        $payload = WebhookPayload::fromArray($data);

        $this->assertInstanceOf(StatusChangedPayload::class, $payload);
        $this->assertTrue($payload->isStatusChanged());
        $this->assertFalse($payload->isPaymentNotification());
        $this->assertFalse($payload->isInvoiceRequest());
    }

    #[Test]
    public function test_from_array_creates_payment_payload(): void
    {
        $data = $this->createPaymentData();

        $payload = WebhookPayload::fromArray($data);

        $this->assertInstanceOf(PaymentPayload::class, $payload);
        $this->assertFalse($payload->isStatusChanged());
        $this->assertTrue($payload->isPaymentNotification());
        $this->assertFalse($payload->isInvoiceRequest());
    }

    #[Test]
    public function test_from_array_creates_invoice_payload(): void
    {
        $data = $this->createInvoiceData();

        $payload = WebhookPayload::fromArray($data);

        $this->assertInstanceOf(InvoicePayload::class, $payload);
        $this->assertFalse($payload->isStatusChanged());
        $this->assertFalse($payload->isPaymentNotification());
        $this->assertTrue($payload->isInvoiceRequest());
    }

    #[Test]
    public function test_from_array_throws_for_unknown_command_type(): void
    {
        $data = [
            'metaData' => [
                'commandType' => 'UNKNOWN_COMMAND',
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown webhook command type: "UNKNOWN_COMMAND"');

        WebhookPayload::fromArray($data);
    }

    #[Test]
    public function test_from_array_throws_for_empty_command_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown webhook command type: ""');

        WebhookPayload::fromArray([]);
    }

    #[Test]
    public function test_command_type_constants(): void
    {
        $this->assertSame('TRANSACTION_STATUS_CHANGED', WebhookPayload::COMMAND_STATUS_CHANGED);
        $this->assertSame('TRANSACTION_MERCHANT_PAYMENT', WebhookPayload::COMMAND_MERCHANT_PAYMENT);
        $this->assertSame('CUSTOMER_RECURRING_ORDER_REQUEST', WebhookPayload::COMMAND_RECURRING_ORDER_REQUEST);
    }

    /**
     * @return array<string, mixed>
     */
    private function createStatusChangedData(): array
    {
        return [
            'metaData' => [
                'commandType' => 'TRANSACTION_STATUS_CHANGED',
                'commandRef' => 'ref-123',
                'commandDateTime' => [2024, 1, 15, 10, 30, 0, 0],
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
                    'agreementNetValue' => 1000.0,
                    'originCompany' => 'BACCA_PAY',
                    'templateName' => 'test_template',
                    'templateFileVersion' => '1.0',
                    'externalRef' => 'ORDER-123',
                    'agreementNumber' => 'AGR-456',
                    'items' => [
                        ['name' => 'Test Item', 'totalValue' => 1000.0],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createPaymentData(): array
    {
        return [
            'metaData' => [
                'commandType' => 'TRANSACTION_MERCHANT_PAYMENT',
                'commandRef' => 'pay-ref-123',
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
                    'agreementNetValue' => 1000.0,
                    'originCompany' => 'BACCA_PAY',
                    'templateName' => 'test_template',
                    'templateFileVersion' => '1.0',
                    'externalRef' => 'ORDER-123',
                    'agreementNumber' => 'AGR-456',
                    'paymentDate' => '2024-01-20',
                    'paymentAmount' => 950.0,
                    'paymentTitle' => 'Payment for ORDER-123',
                    'beneficiaryAccountNumber' => 'PL12345678901234567890123456',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createInvoiceData(): array
    {
        return [
            'metaData' => [
                'commandType' => 'CUSTOMER_RECURRING_ORDER_REQUEST',
                'commandRef' => 'inv-ref-123',
                'commandDateTime' => [2024, 2, 1, 9, 0, 0, 0],
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
                    'agreementNetValue' => 1000.0,
                    'originCompany' => 'BACCA_PAY',
                    'templateName' => 'test_template',
                    'templateFileVersion' => '1.0',
                    'externalRef' => 'ORDER-123',
                    'agreementNumber' => 'AGR-456',
                    'requestTotalAmount' => 333.33,
                    'requestPositions' => [
                        ['rateNumber' => 1, 'totalAmount' => 333.33, 'paymentScheduleId' => 'PS-001'],
                    ],
                ],
            ],
        ];
    }
}
