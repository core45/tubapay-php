<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\Security;

use Core45\TubaPay\Exception\WebhookVerificationException;
use Core45\TubaPay\Security\SignatureVerifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SignatureVerifierTest extends TestCase
{
    private const TEST_SECRET = 'test-webhook-secret-key';

    #[Test]
    public function test_verify_valid_signature(): void
    {
        $verifier = new SignatureVerifier(self::TEST_SECRET);
        $payload = '{"test":"data"}';
        $signature = $verifier->computeSignature($payload);

        // Should not throw
        $verifier->verify($payload, $signature);
        $this->assertTrue(true);
    }

    #[Test]
    public function test_verify_throws_on_invalid_signature(): void
    {
        $verifier = new SignatureVerifier(self::TEST_SECRET);
        $payload = '{"test":"data"}';
        $invalidSignature = 'invalid-signature';

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('Invalid signature');

        $verifier->verify($payload, $invalidSignature);
    }

    #[Test]
    public function test_verify_throws_on_empty_payload(): void
    {
        $verifier = new SignatureVerifier(self::TEST_SECRET);

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('Empty payload');

        $verifier->verify('', 'some-signature');
    }

    #[Test]
    public function test_verify_throws_on_missing_signature(): void
    {
        $verifier = new SignatureVerifier(self::TEST_SECRET);

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('X-TUBAPAY-CHECKSUM');

        $verifier->verify('{"test":"data"}', '');
    }

    #[Test]
    public function test_is_valid_returns_true_for_valid_signature(): void
    {
        $verifier = new SignatureVerifier(self::TEST_SECRET);
        $payload = '{"test":"data"}';
        $signature = $verifier->computeSignature($payload);

        $this->assertTrue($verifier->isValid($payload, $signature));
    }

    #[Test]
    public function test_is_valid_returns_false_for_invalid_signature(): void
    {
        $verifier = new SignatureVerifier(self::TEST_SECRET);
        $payload = '{"test":"data"}';

        $this->assertFalse($verifier->isValid($payload, 'wrong-signature'));
    }

    #[Test]
    public function test_is_valid_returns_false_for_empty_payload(): void
    {
        $verifier = new SignatureVerifier(self::TEST_SECRET);

        $this->assertFalse($verifier->isValid('', 'signature'));
    }

    #[Test]
    public function test_is_valid_returns_false_for_empty_signature(): void
    {
        $verifier = new SignatureVerifier(self::TEST_SECRET);

        $this->assertFalse($verifier->isValid('{"test":"data"}', ''));
    }

    #[Test]
    public function test_compute_signature_returns_base64(): void
    {
        $verifier = new SignatureVerifier(self::TEST_SECRET);
        $payload = '{"test":"data"}';

        $signature = $verifier->computeSignature($payload);

        // Should be valid base64
        $this->assertNotFalse(base64_decode($signature, true));
    }

    #[Test]
    public function test_signature_changes_with_payload(): void
    {
        $verifier = new SignatureVerifier(self::TEST_SECRET);

        $sig1 = $verifier->computeSignature('{"a":"1"}');
        $sig2 = $verifier->computeSignature('{"a":"2"}');

        $this->assertNotSame($sig1, $sig2);
    }

    #[Test]
    public function test_signature_changes_with_secret(): void
    {
        $payload = '{"test":"data"}';

        $verifier1 = new SignatureVerifier('secret-1');
        $verifier2 = new SignatureVerifier('secret-2');

        $sig1 = $verifier1->computeSignature($payload);
        $sig2 = $verifier2->computeSignature($payload);

        $this->assertNotSame($sig1, $sig2);
    }

    #[Test]
    public function test_signature_is_deterministic(): void
    {
        $verifier = new SignatureVerifier(self::TEST_SECRET);
        $payload = '{"test":"data"}';

        $sig1 = $verifier->computeSignature($payload);
        $sig2 = $verifier->computeSignature($payload);

        $this->assertSame($sig1, $sig2);
    }

    #[Test]
    public function test_header_name_constant(): void
    {
        $this->assertSame('X-TUBAPAY-CHECKSUM', SignatureVerifier::HEADER_NAME);
    }

    #[Test]
    public function test_verify_is_timing_safe(): void
    {
        // This test verifies that we use hash_equals for comparison
        // We can't easily test timing-safety, but we can verify behavior
        $verifier = new SignatureVerifier(self::TEST_SECRET);
        $payload = '{"test":"data"}';
        $validSignature = $verifier->computeSignature($payload);

        // Similar but different signatures should both be rejected
        $this->assertFalse($verifier->isValid($payload, substr($validSignature, 0, -1)));
        $this->assertFalse($verifier->isValid($payload, $validSignature . 'x'));
    }
}
