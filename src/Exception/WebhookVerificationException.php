<?php

declare(strict_types=1);

namespace Core45\TubaPay\Exception;

/**
 * Exception thrown when webhook signature verification fails.
 *
 * This indicates a potential security issue - the webhook request
 * may not be from TubaPay or may have been tampered with.
 */
class WebhookVerificationException extends TubaPayException
{
    /**
     * Create exception for invalid signature.
     */
    public static function invalidSignature(): self
    {
        return new self(
            'Webhook verification failed: Invalid signature.',
            403,
            context: ['reason' => 'invalid_signature']
        );
    }

    /**
     * Create exception for missing signature header.
     */
    public static function missingSignature(): self
    {
        return new self(
            'Webhook verification failed: Missing X-TUBAPAY-CHECKSUM header.',
            403,
            context: ['reason' => 'missing_signature']
        );
    }

    /**
     * Create exception for empty payload.
     */
    public static function emptyPayload(): self
    {
        return new self(
            'Webhook verification failed: Empty payload.',
            400,
            context: ['reason' => 'empty_payload']
        );
    }
}
