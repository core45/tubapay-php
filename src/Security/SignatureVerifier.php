<?php

declare(strict_types=1);

namespace Core45\TubaPay\Security;

use Core45\TubaPay\Exception\WebhookVerificationException;

/**
 * Verifies HMAC-SHA512 signatures from TubaPay webhooks.
 *
 * TubaPay sends a signature in the X-TUBAPAY-CHECKSUM header.
 * The signature is computed as per official API docs:
 * 1. Base64 encode the raw payload
 * 2. HMAC-SHA512 the base64 string with partner secret
 * 3. Return lowercase hex string
 */
final class SignatureVerifier
{
    private const ALGORITHM = 'sha512';
    public const HEADER_NAME = 'X-TUBAPAY-CHECKSUM';

    public function __construct(
        private readonly string $secret,
    ) {}

    /**
     * Verify a webhook payload signature.
     *
     * @param string $payload Raw JSON payload body
     * @param string $signature Hex-encoded HMAC-SHA512 signature from header
     *
     * @throws WebhookVerificationException If signature is invalid
     */
    public function verify(string $payload, string $signature): void
    {
        if (empty($payload)) {
            throw WebhookVerificationException::emptyPayload();
        }

        if (empty($signature)) {
            throw WebhookVerificationException::missingSignature();
        }

        $expectedSignature = $this->computeSignature($payload);

        if (!hash_equals($expectedSignature, strtolower($signature))) {
            throw WebhookVerificationException::invalidSignature();
        }
    }

    /**
     * Check if a webhook payload signature is valid.
     *
     * @param string $payload Raw JSON payload body
     * @param string $signature Hex-encoded HMAC-SHA512 signature from header
     */
    public function isValid(string $payload, string $signature): bool
    {
        if (empty($payload) || empty($signature)) {
            return false;
        }

        $expectedSignature = $this->computeSignature($payload);

        return hash_equals($expectedSignature, strtolower($signature));
    }

    /**
     * Compute the expected signature for a payload.
     *
     * Algorithm per TubaPay API docs:
     * 1. Base64 encode the raw payload
     * 2. HMAC-SHA512 the base64 string with secret
     * 3. Return lowercase hex string
     */
    public function computeSignature(string $payload): string
    {
        // Step 1: Base64 encode the raw payload
        $base64Payload = base64_encode($payload);

        // Step 2 & 3: HMAC-SHA512 and return hex (not binary)
        return hash_hmac(self::ALGORITHM, $base64Payload, $this->secret);
    }
}
