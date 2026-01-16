<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO\Webhook;

use Core45\TubaPay\DTO\Customer;
use Core45\TubaPay\DTO\OrderItem;
use Core45\TubaPay\Enum\AgreementStatus;

/**
 * Webhook payload for TRANSACTION_STATUS_CHANGED events.
 */
readonly class StatusChangedPayload extends WebhookPayload
{
    /**
     * @param array<string, mixed> $rawPayload
     * @param list<OrderItem> $items
     */
    public function __construct(
        string $commandType,
        string $commandRef,
        ?\DateTimeImmutable $commandDateTime,
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
        );
    }

    /**
     * Check if the agreement was accepted (payment will be made).
     */
    public function isAccepted(): bool
    {
        return $this->agreementStatus === AgreementStatus::Accepted;
    }

    /**
     * Check if the agreement was rejected (no payment).
     */
    public function isRejected(): bool
    {
        return $this->agreementStatus === AgreementStatus::Rejected;
    }

    /**
     * Check if this is a final status (no more updates expected).
     */
    public function isFinalStatus(): bool
    {
        return $this->agreementStatus->isFinal();
    }
}
