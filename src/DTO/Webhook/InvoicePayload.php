<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO\Webhook;

use Core45\TubaPay\DTO\Customer;
use Core45\TubaPay\DTO\OrderItem;
use Core45\TubaPay\Enum\AgreementStatus;
use DateTimeImmutable;

/**
 * Invoice request position (installment to be invoiced).
 */
readonly class InvoicePosition
{
    public function __construct(
        /** Installment/rate number. */
        public int $rateNumber,
        /** Amount for this installment. */
        public float $totalAmount,
        /** Payment schedule ID (optional). */
        public ?string $paymentScheduleId = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            rateNumber: (int) ($data['rateNumber'] ?? 0),
            totalAmount: (float) ($data['totalAmount'] ?? 0.0),
            paymentScheduleId: isset($data['paymentScheduleId']) ? (string) $data['paymentScheduleId'] : null,
        );
    }
}

/**
 * Webhook payload for CUSTOMER_RECURRING_ORDER_REQUEST events.
 *
 * Request from TubaPay to issue an invoice to the customer for their installment.
 */
readonly class InvoicePayload extends WebhookPayload
{
    /**
     * @param array<string, mixed> $rawPayload
     * @param list<OrderItem> $items
     * @param list<InvoicePosition> $requestPositions
     */
    public function __construct(
        string $commandType,
        string $commandRef,
        ?DateTimeImmutable $commandDateTime,
        string $commandCallbackUrl,
        string $commandCallbackType,
        string $tubapayPartnerId,
        string $partnerName,
        ?string $externalRef,
        ?string $agreementNumber,
        array $rawPayload,
        /** Current agreement status. */
        public AgreementStatus $agreementStatus,
        /** Agreement value (transaction amount). */
        public float $agreementNetValue,
        /** Company handling the agreement. */
        public string $originCompany,
        /** Template name used for this agreement. */
        public string $templateName,
        /** Template file version. */
        public string $templateFileVersion,
        /** Customer data. */
        public Customer $customer,
        /** Order items. */
        public array $items,
        /** Total amount to invoice. */
        public float $requestTotalAmount,
        /** Invoice positions (installments). */
        public array $requestPositions,
    ) {
        parent::__construct(
            $commandType,
            $commandRef,
            $commandDateTime,
            $commandCallbackUrl,
            $commandCallbackType,
            $tubapayPartnerId,
            $partnerName,
            $externalRef,
            $agreementNumber,
            $rawPayload
        );
    }

    /**
     * Create from webhook payload array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $metaData = $data['metaData'] ?? [];
        $payload = $data['payload'] ?? [];
        $partner = $payload['partner'] ?? [];
        $customerData = $payload['customer'] ?? [];
        $transaction = $payload['transaction'] ?? [];

        $items = [];
        foreach (($transaction['items'] ?? []) as $itemData) {
            if (is_array($itemData)) {
                $items[] = OrderItem::fromArray($itemData);
            }
        }

        $positions = [];
        foreach (($transaction['requestPositions'] ?? []) as $positionData) {
            if (is_array($positionData)) {
                $positions[] = InvoicePosition::fromArray($positionData);
            }
        }

        return new self(
            commandType: (string) ($metaData['commandType'] ?? ''),
            commandRef: (string) ($metaData['commandRef'] ?? ''),
            commandDateTime: self::parseDateTime($metaData['commandDateTime'] ?? null),
            commandCallbackUrl: (string) ($metaData['commandCallbackUrl'] ?? ''),
            commandCallbackType: (string) ($metaData['commandCallbackType'] ?? ''),
            tubapayPartnerId: (string) ($partner['tubapayPartnerId'] ?? ''),
            partnerName: (string) ($partner['partnerName'] ?? ''),
            externalRef: isset($transaction['externalRef']) ? (string) $transaction['externalRef'] : null,
            agreementNumber: isset($transaction['agreementNumber']) ? (string) $transaction['agreementNumber'] : null,
            rawPayload: $data,
            agreementStatus: AgreementStatus::fromString((string) ($transaction['agreementStatus'] ?? 'draft')),
            agreementNetValue: (float) ($transaction['agreementNetValue'] ?? 0.0),
            originCompany: (string) ($transaction['originCompany'] ?? ''),
            templateName: (string) ($transaction['templateName'] ?? ''),
            templateFileVersion: (string) ($transaction['templateFileVersion'] ?? ''),
            customer: Customer::fromArray($customerData),
            items: $items,
            requestTotalAmount: (float) ($transaction['requestTotalAmount'] ?? 0.0),
            requestPositions: $positions,
        );
    }

    /**
     * Get the first invoice position (usually there's only one).
     */
    public function getFirstPosition(): ?InvoicePosition
    {
        return $this->requestPositions[0] ?? null;
    }
}
