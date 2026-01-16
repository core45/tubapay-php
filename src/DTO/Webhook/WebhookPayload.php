<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO\Webhook;

use DateTimeImmutable;

/**
 * Base webhook payload from TubaPay.
 */
abstract readonly class WebhookPayload
{
    public const COMMAND_STATUS_CHANGED = 'TRANSACTION_STATUS_CHANGED';
    public const COMMAND_MERCHANT_PAYMENT = 'TRANSACTION_MERCHANT_PAYMENT';
    public const COMMAND_RECURRING_ORDER_REQUEST = 'CUSTOMER_RECURRING_ORDER_REQUEST';

    /**
     * @param array<string, mixed> $rawPayload Raw payload data.
     */
    public function __construct(
        /** Type of webhook command. */
        public string $commandType,
        /** Unique reference ID for this webhook call. */
        public string $commandRef,
        /** Timestamp of the webhook call. */
        public ?DateTimeImmutable $commandDateTime,
        /** URL where this webhook was sent. */
        public string $commandCallbackUrl,
        /** Callback type (usually "custom"). */
        public string $commandCallbackType,
        /** Partner's TubaPay ID. */
        public string $tubapayPartnerId,
        /** Partner's name. */
        public string $partnerName,
        /** Customer's external reference (your order ID). */
        public ?string $externalRef,
        /** TubaPay agreement number. */
        public ?string $agreementNumber,
        public array $rawPayload,
    ) {}

    /**
     * Determine the webhook type and create appropriate DTO.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $metaData = $data['metaData'] ?? [];
        $commandType = (string) ($metaData['commandType'] ?? '');

        return match ($commandType) {
            self::COMMAND_STATUS_CHANGED => StatusChangedPayload::fromArray($data),
            self::COMMAND_MERCHANT_PAYMENT => PaymentPayload::fromArray($data),
            self::COMMAND_RECURRING_ORDER_REQUEST => InvoicePayload::fromArray($data),
            default => throw new \InvalidArgumentException(
                sprintf('Unknown webhook command type: "%s"', $commandType)
            ),
        };
    }

    /**
     * Parse command datetime from various formats.
     */
    protected static function parseDateTime(mixed $dateTime): ?DateTimeImmutable
    {
        if ($dateTime === null) {
            return null;
        }

        // Handle array format [year, month, day, hour, minute, second, nanoseconds]
        if (is_array($dateTime)) {
            /** @var array<int, int> $dateTime */
            return new DateTimeImmutable(sprintf(
                '%04d-%02d-%02d %02d:%02d:%02d',
                $dateTime[0] ?? 0,
                $dateTime[1] ?? 1,
                $dateTime[2] ?? 1,
                $dateTime[3] ?? 0,
                $dateTime[4] ?? 0,
                $dateTime[5] ?? 0
            ));
        }

        // Handle string format
        if (is_string($dateTime)) {
            try {
                return new DateTimeImmutable($dateTime);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }

    /**
     * Check if this is a status change webhook.
     */
    public function isStatusChanged(): bool
    {
        return $this->commandType === self::COMMAND_STATUS_CHANGED;
    }

    /**
     * Check if this is a payment notification webhook.
     */
    public function isPaymentNotification(): bool
    {
        return $this->commandType === self::COMMAND_MERCHANT_PAYMENT;
    }

    /**
     * Check if this is an invoice request webhook.
     */
    public function isInvoiceRequest(): bool
    {
        return $this->commandType === self::COMMAND_RECURRING_ORDER_REQUEST;
    }
}
