<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\DTO\Webhook;

use Core45\TubaPay\DTO\Webhook\PaymentPayload;
use Core45\TubaPay\Enum\AgreementStatus;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PaymentPayloadTest extends TestCase
{
    #[Test]
    public function test_from_array_parses_complete_payload(): void
    {
        $data = $this->createCompletePayload();

        $payload = PaymentPayload::fromArray($data);

        // Meta data
        $this->assertSame('TRANSACTION_MERCHANT_PAYMENT', $payload->commandType);
        $this->assertSame('pay-cmd-123', $payload->commandRef);
        $this->assertInstanceOf(DateTimeImmutable::class, $payload->commandDateTime);
        $this->assertSame('https://example.com/webhook', $payload->commandCallbackUrl);

        // Partner
        $this->assertSame('703419', $payload->tubapayPartnerId);
        $this->assertSame('Test Partner', $payload->partnerName);

        // Transaction
        $this->assertSame(AgreementStatus::Accepted, $payload->agreementStatus);
        $this->assertSame(2000.0, $payload->agreementNetValue);
        $this->assertSame('BACCA_PAY', $payload->originCompany);
        $this->assertSame('ORDER-PAY-123', $payload->externalRef);
        $this->assertSame('AGR-PAY-456', $payload->agreementNumber);

        // Payment specific fields
        $this->assertInstanceOf(DateTimeImmutable::class, $payload->paymentDate);
        $this->assertSame('2024-01-20', $payload->paymentDate->format('Y-m-d'));
        $this->assertSame(1900.0, $payload->paymentAmount);
        $this->assertSame('Payment for ORDER-PAY-123', $payload->paymentTitle);
        $this->assertSame('PL12345678901234567890123456', $payload->beneficiaryAccountNumber);

        // Customer
        $this->assertSame('Jan', $payload->customer->firstName);
        $this->assertSame('Kowalski', $payload->customer->lastName);

        // Items
        $this->assertCount(1, $payload->items);
        $this->assertSame('Premium Product', $payload->items[0]->name);
    }

    #[Test]
    public function test_from_array_handles_missing_payment_date(): void
    {
        $data = $this->createPayloadWithoutPaymentDate();

        $payload = PaymentPayload::fromArray($data);

        $this->assertNull($payload->paymentDate);
        $this->assertSame(0.0, $payload->paymentAmount);
        $this->assertSame('', $payload->paymentTitle);
        $this->assertSame('', $payload->beneficiaryAccountNumber);
    }

    #[Test]
    public function test_from_array_handles_payment_date_array_format(): void
    {
        $data = [
            'metaData' => ['commandType' => 'TRANSACTION_MERCHANT_PAYMENT'],
            'payload' => [
                'transaction' => [
                    'agreementStatus' => 'accepted',
                    'paymentDate' => [2024, 1, 25, 14, 30, 0, 0],
                    'paymentAmount' => 500.0,
                ],
            ],
        ];

        $payload = PaymentPayload::fromArray($data);

        $this->assertInstanceOf(DateTimeImmutable::class, $payload->paymentDate);
        $this->assertSame('2024-01-25 14:30:00', $payload->paymentDate->format('Y-m-d H:i:s'));
        $this->assertSame(500.0, $payload->paymentAmount);
    }

    #[Test]
    public function test_from_array_handles_empty_payload(): void
    {
        $data = [
            'metaData' => ['commandType' => 'TRANSACTION_MERCHANT_PAYMENT'],
        ];

        $payload = PaymentPayload::fromArray($data);

        $this->assertSame('TRANSACTION_MERCHANT_PAYMENT', $payload->commandType);
        $this->assertSame('', $payload->commandRef);
        $this->assertSame(AgreementStatus::Draft, $payload->agreementStatus);
        $this->assertNull($payload->paymentDate);
        $this->assertSame(0.0, $payload->paymentAmount);
        $this->assertSame('', $payload->paymentTitle);
        $this->assertSame('', $payload->beneficiaryAccountNumber);
        $this->assertCount(0, $payload->items);
    }

    #[Test]
    public function test_is_payment_notification_returns_true(): void
    {
        $data = $this->createCompletePayload();
        $payload = PaymentPayload::fromArray($data);

        $this->assertTrue($payload->isPaymentNotification());
        $this->assertFalse($payload->isStatusChanged());
        $this->assertFalse($payload->isInvoiceRequest());
    }

    #[Test]
    public function test_parses_multiple_items(): void
    {
        $data = [
            'metaData' => ['commandType' => 'TRANSACTION_MERCHANT_PAYMENT'],
            'payload' => [
                'transaction' => [
                    'agreementStatus' => 'accepted',
                    'items' => [
                        ['name' => 'Item 1', 'totalValue' => 100.0],
                        ['name' => 'Item 2', 'totalValue' => 200.0],
                    ],
                ],
            ],
        ];

        $payload = PaymentPayload::fromArray($data);

        $this->assertCount(2, $payload->items);
        $this->assertSame('Item 1', $payload->items[0]->name);
        $this->assertSame('Item 2', $payload->items[1]->name);
    }

    /**
     * @return array<string, mixed>
     */
    private function createCompletePayload(): array
    {
        return [
            'metaData' => [
                'commandType' => 'TRANSACTION_MERCHANT_PAYMENT',
                'commandRef' => 'pay-cmd-123',
                'commandDateTime' => '2024-01-20T09:00:00Z',
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
                    'agreementNetValue' => 2000.0,
                    'originCompany' => 'BACCA_PAY',
                    'templateName' => 'payment_template',
                    'templateFileVersion' => '1.0',
                    'externalRef' => 'ORDER-PAY-123',
                    'agreementNumber' => 'AGR-PAY-456',
                    'paymentDate' => '2024-01-20',
                    'paymentAmount' => 1900.0,
                    'paymentTitle' => 'Payment for ORDER-PAY-123',
                    'beneficiaryAccountNumber' => 'PL12345678901234567890123456',
                    'items' => [
                        ['name' => 'Premium Product', 'totalValue' => 2000.0],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createPayloadWithoutPaymentDate(): array
    {
        return [
            'metaData' => ['commandType' => 'TRANSACTION_MERCHANT_PAYMENT'],
            'payload' => [
                'transaction' => [
                    'agreementStatus' => 'accepted',
                    'agreementNetValue' => 1000.0,
                ],
            ],
        ];
    }
}
