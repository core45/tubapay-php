<?php

declare(strict_types=1);

namespace Core45\TubaPay;

use Core45\TubaPay\Api\ContentApi;
use Core45\TubaPay\Api\OfferApi;
use Core45\TubaPay\Api\TransactionApi;
use Core45\TubaPay\Api\UiTextApi;
use Core45\TubaPay\DTO\ConnectionStatus;
use Core45\TubaPay\DTO\Webhook\WebhookPayload;
use Core45\TubaPay\Enum\Environment;
use Core45\TubaPay\Exception\WebhookVerificationException;
use Core45\TubaPay\Http\InMemoryTokenStorage;
use Core45\TubaPay\Http\TokenStorageInterface;
use Core45\TubaPay\Http\TubaPayClient;
use Core45\TubaPay\Security\SignatureVerifier;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Main entry point for TubaPay SDK.
 *
 * Example usage:
 * ```php
 * $tubapay = TubaPay::create(
 *     clientId: 'your-client-id',
 *     clientSecret: 'your-client-secret',
 *     webhookSecret: 'your-webhook-secret',
 *     environment: Environment::Test,
 * );
 *
 * // Create an offer
 * $offer = $tubapay->offers()->createOffer(
 *     amount: 1000.0,
 *     customer: $customer,
 *     item: $orderItem,
 * );
 *
 * // Create a transaction
 * $transaction = $tubapay->transactions()->createTransaction(
 *     customer: $customer,
 *     item: $orderItem,
 *     installments: 6,
 *     callbackUrl: 'https://yoursite.com/webhook',
 * );
 *
 * // Handle webhook
 * $payload = $tubapay->verifyAndParseWebhook(
 *     $request->getContent(),
 *     $request->header('X-TUBAPAY-CHECKSUM'),
 * );
 * ```
 */
final class TubaPay
{
    private readonly TubaPayClient $client;

    private readonly SignatureVerifier $signatureVerifier;

    private ?OfferApi $offerApi = null;

    private ?TransactionApi $transactionApi = null;

    private ?UiTextApi $uiTextApi = null;

    private ?ContentApi $contentApi = null;

    private function __construct(
        TubaPayClient $client,
        SignatureVerifier $signatureVerifier,
    ) {
        $this->client = $client;
        $this->signatureVerifier = $signatureVerifier;
    }

    /**
     * Create a TubaPay instance.
     *
     * @param  string  $clientId  Your TubaPay partner client ID
     * @param  string  $clientSecret  Your TubaPay partner client secret
     * @param  string  $webhookSecret  Your webhook verification secret
     * @param  Environment  $environment  API environment (Test or Production)
     * @param  TokenStorageInterface|null  $tokenStorage  Optional custom token storage
     * @param  ClientInterface|null  $httpClient  Optional custom Guzzle client
     * @param  array<string, mixed>  $guzzleOptions  Additional Guzzle options
     * @param  LoggerInterface|null  $logger  Optional PSR-3 logger for debugging API calls
     */
    public static function create(
        string $clientId,
        string $clientSecret,
        string $webhookSecret,
        Environment $environment = Environment::Test,
        ?TokenStorageInterface $tokenStorage = null,
        ?ClientInterface $httpClient = null,
        array $guzzleOptions = [],
        ?LoggerInterface $logger = null,
    ): self {
        $client = new TubaPayClient(
            $clientId,
            $clientSecret,
            $environment,
            $tokenStorage ?? new InMemoryTokenStorage,
            $httpClient,
            $guzzleOptions,
            $logger,
        );

        $signatureVerifier = new SignatureVerifier($webhookSecret);

        return new self($client, $signatureVerifier);
    }

    /**
     * Get the Offer API for creating installment offers.
     */
    public function offers(): OfferApi
    {
        if ($this->offerApi === null) {
            $this->offerApi = new OfferApi($this->client);
        }

        return $this->offerApi;
    }

    /**
     * Get the Transaction API for creating payment transactions.
     */
    public function transactions(): TransactionApi
    {
        if ($this->transactionApi === null) {
            $this->transactionApi = new TransactionApi($this->client);
        }

        return $this->transactionApi;
    }

    /**
     * Get the UI Text API for checkout labels.
     */
    public function uiTexts(): UiTextApi
    {
        if ($this->uiTextApi === null) {
            $this->uiTextApi = new UiTextApi($this->client);
        }

        return $this->uiTextApi;
    }

    /**
     * Get the Content API for TubaPay promotional content.
     */
    public function content(): ContentApi
    {
        if ($this->contentApi === null) {
            $this->contentApi = new ContentApi($this->client);
        }

        return $this->contentApi;
    }

    /**
     * Check whether credentials can authenticate against TubaPay.
     */
    public function checkConnection(): ConnectionStatus
    {
        try {
            return ConnectionStatus::successful(
                $this->client->getTokenManager()->requestToken()
            );
        } catch (Throwable $e) {
            return ConnectionStatus::failed($e->getMessage());
        }
    }

    /**
     * Verify a webhook signature and parse the payload.
     *
     * @param  string  $payload  Raw JSON webhook body
     * @param  string|null  $signature  X-TUBAPAY-CHECKSUM header value
     *
     * @throws WebhookVerificationException If signature is invalid
     */
    public function verifyAndParseWebhook(string $payload, ?string $signature): WebhookPayload
    {
        $this->signatureVerifier->verify($payload, $signature ?? '');

        $data = json_decode($payload, true);

        if (! is_array($data)) {
            throw WebhookVerificationException::emptyPayload();
        }

        return WebhookPayload::fromArray($data);
    }

    /**
     * Parse a webhook payload without signature verification.
     *
     * Use this only when you've already verified the signature
     * or in trusted environments (e.g., when receiving webhooks
     * through an already authenticated channel).
     *
     * @param  string  $payload  Raw JSON webhook body
     */
    public function parseWebhook(string $payload): WebhookPayload
    {
        $data = json_decode($payload, true);

        if (! is_array($data)) {
            throw WebhookVerificationException::emptyPayload();
        }

        return WebhookPayload::fromArray($data);
    }

    /**
     * Get the underlying HTTP client.
     *
     * Useful for making custom API calls or accessing token management.
     */
    public function getClient(): TubaPayClient
    {
        return $this->client;
    }

    /**
     * Get the signature verifier.
     *
     * Useful for manual signature verification.
     */
    public function getSignatureVerifier(): SignatureVerifier
    {
        return $this->signatureVerifier;
    }

    /**
     * Get the current API environment.
     */
    public function getEnvironment(): Environment
    {
        return $this->client->getEnvironment();
    }
}
