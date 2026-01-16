<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit;

use Core45\TubaPay\Api\OfferApi;
use Core45\TubaPay\Api\TransactionApi;
use Core45\TubaPay\DTO\Webhook\StatusChangedPayload;
use Core45\TubaPay\Enum\Environment;
use Core45\TubaPay\Exception\WebhookVerificationException;
use Core45\TubaPay\Http\TubaPayClient;
use Core45\TubaPay\Security\SignatureVerifier;
use Core45\TubaPay\TubaPay;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TubaPayTest extends TestCase
{
    private const CLIENT_ID = 'test-client-id';
    private const CLIENT_SECRET = 'test-client-secret';
    private const WEBHOOK_SECRET = 'test-webhook-secret';

    #[Test]
    public function test_create_instance(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $this->assertInstanceOf(TubaPay::class, $tubapay);
    }

    #[Test]
    public function test_create_with_environment(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
            environment: Environment::Production,
        );

        $this->assertSame(Environment::Production, $tubapay->getEnvironment());
    }

    #[Test]
    public function test_default_environment_is_test(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $this->assertSame(Environment::Test, $tubapay->getEnvironment());
    }

    #[Test]
    public function test_offers_returns_offer_api(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $offers = $tubapay->offers();

        $this->assertInstanceOf(OfferApi::class, $offers);
    }

    #[Test]
    public function test_offers_returns_same_instance(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $offers1 = $tubapay->offers();
        $offers2 = $tubapay->offers();

        $this->assertSame($offers1, $offers2);
    }

    #[Test]
    public function test_transactions_returns_transaction_api(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $transactions = $tubapay->transactions();

        $this->assertInstanceOf(TransactionApi::class, $transactions);
    }

    #[Test]
    public function test_transactions_returns_same_instance(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $transactions1 = $tubapay->transactions();
        $transactions2 = $tubapay->transactions();

        $this->assertSame($transactions1, $transactions2);
    }

    #[Test]
    public function test_get_client(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $client = $tubapay->getClient();

        $this->assertInstanceOf(TubaPayClient::class, $client);
    }

    #[Test]
    public function test_get_signature_verifier(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $verifier = $tubapay->getSignatureVerifier();

        $this->assertInstanceOf(SignatureVerifier::class, $verifier);
    }

    #[Test]
    public function test_verify_and_parse_webhook(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $payload = json_encode($this->createWebhookPayload(), JSON_THROW_ON_ERROR);
        $signature = $tubapay->getSignatureVerifier()->computeSignature($payload);

        $webhookPayload = $tubapay->verifyAndParseWebhook($payload, $signature);

        $this->assertInstanceOf(StatusChangedPayload::class, $webhookPayload);
    }

    #[Test]
    public function test_verify_and_parse_webhook_throws_on_invalid_signature(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $payload = json_encode($this->createWebhookPayload(), JSON_THROW_ON_ERROR);

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('Invalid signature');

        $tubapay->verifyAndParseWebhook($payload, 'invalid-signature');
    }

    #[Test]
    public function test_verify_and_parse_webhook_throws_on_null_signature(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $payload = json_encode($this->createWebhookPayload(), JSON_THROW_ON_ERROR);

        $this->expectException(WebhookVerificationException::class);

        $tubapay->verifyAndParseWebhook($payload, null);
    }

    #[Test]
    public function test_parse_webhook_without_verification(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $payload = json_encode($this->createWebhookPayload(), JSON_THROW_ON_ERROR);

        // No signature verification - useful for trusted environments
        $webhookPayload = $tubapay->parseWebhook($payload);

        $this->assertInstanceOf(StatusChangedPayload::class, $webhookPayload);
    }

    #[Test]
    public function test_parse_webhook_throws_on_invalid_json(): void
    {
        $tubapay = TubaPay::create(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            webhookSecret: self::WEBHOOK_SECRET,
        );

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('Empty payload');

        $tubapay->parseWebhook('not json');
    }

    /**
     * @return array<string, mixed>
     */
    private function createWebhookPayload(): array
    {
        return [
            'metaData' => [
                'commandType' => 'TRANSACTION_STATUS_CHANGED',
                'commandRef' => 'ref-123',
                'commandDateTime' => '2024-01-15T10:00:00Z',
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
                ],
            ],
        ];
    }
}
