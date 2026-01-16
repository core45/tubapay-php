<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\Exception;

use Core45\TubaPay\Exception\ApiException;
use Core45\TubaPay\Exception\AuthenticationException;
use Core45\TubaPay\Exception\TubaPayException;
use Core45\TubaPay\Exception\ValidationException;
use Core45\TubaPay\Exception\WebhookVerificationException;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExceptionsTest extends TestCase
{
    #[Test]
    public function test_tubapay_exception_stores_context(): void
    {
        $context = ['key' => 'value', 'nested' => ['data' => true]];
        $exception = new TubaPayException('Test message', 500, null, $context);

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame($context, $exception->getContext());
    }

    #[Test]
    public function test_authentication_exception_invalid_credentials(): void
    {
        $exception = AuthenticationException::invalidCredentials();

        $this->assertSame('Authentication failed: Invalid client credentials.', $exception->getMessage());
        $this->assertSame(401, $exception->getCode());
        $this->assertSame(['reason' => 'invalid_credentials'], $exception->getContext());
    }

    #[Test]
    public function test_authentication_exception_token_expired(): void
    {
        $exception = AuthenticationException::tokenExpired();

        $this->assertSame('Authentication failed: Token has expired.', $exception->getMessage());
        $this->assertSame(401, $exception->getCode());
        $this->assertSame(['reason' => 'token_expired'], $exception->getContext());
    }

    #[Test]
    public function test_authentication_exception_missing_credentials(): void
    {
        $exception = AuthenticationException::missingCredentials();

        $this->assertSame('Authentication failed: Client ID and secret are required.', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame(['reason' => 'missing_credentials'], $exception->getContext());
    }

    #[Test]
    public function test_validation_exception_stores_errors(): void
    {
        $errors = ['email' => 'Invalid email format', 'phone' => 'Required'];
        $exception = new ValidationException('Validation failed', $errors);

        $this->assertSame('Validation failed', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame($errors, $exception->getErrors());
        $this->assertSame(['errors' => $errors], $exception->getContext());
    }

    #[Test]
    public function test_validation_exception_missing_field(): void
    {
        $exception = ValidationException::missingField('email');

        $this->assertSame('Validation failed: Field "email" is required.', $exception->getMessage());
        $this->assertSame(['email' => 'This field is required.'], $exception->getErrors());
    }

    #[Test]
    public function test_validation_exception_invalid_field(): void
    {
        $exception = ValidationException::invalidField('email', 'Must be a valid email address.');

        $this->assertSame(
            'Validation failed: Field "email" is invalid. Must be a valid email address.',
            $exception->getMessage()
        );
        $this->assertSame(['email' => 'Must be a valid email address.'], $exception->getErrors());
    }

    #[Test]
    public function test_validation_exception_amount_out_of_range(): void
    {
        $exception = ValidationException::amountOutOfRange(50.00, 200.00, 50000.00);

        $this->assertStringContainsString('50.00', $exception->getMessage());
        $this->assertStringContainsString('200.00', $exception->getMessage());
        $this->assertStringContainsString('50000.00', $exception->getMessage());
    }

    #[Test]
    public function test_validation_exception_no_products_available(): void
    {
        $exception = ValidationException::noProductsAvailable();

        $this->assertStringContainsString('No installment products available', $exception->getMessage());
    }

    #[Test]
    public function test_webhook_verification_exception_invalid_signature(): void
    {
        $exception = WebhookVerificationException::invalidSignature();

        $this->assertStringContainsString('Invalid signature', $exception->getMessage());
        $this->assertSame(403, $exception->getCode());
        $this->assertSame(['reason' => 'invalid_signature'], $exception->getContext());
    }

    #[Test]
    public function test_webhook_verification_exception_missing_signature(): void
    {
        $exception = WebhookVerificationException::missingSignature();

        $this->assertStringContainsString('X-TUBAPAY-CHECKSUM', $exception->getMessage());
        $this->assertSame(403, $exception->getCode());
        $this->assertSame(['reason' => 'missing_signature'], $exception->getContext());
    }

    #[Test]
    public function test_webhook_verification_exception_empty_payload(): void
    {
        $exception = WebhookVerificationException::emptyPayload();

        $this->assertStringContainsString('Empty payload', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame(['reason' => 'empty_payload'], $exception->getContext());
    }

    #[Test]
    public function test_api_exception_from_response(): void
    {
        $response = [
            'error' => 'Brak przypisanych produktów',
            'requestId' => 'abc123',
        ];

        $exception = ApiException::fromResponse($response, 400, '/api/v1/external/transaction/create-offer');

        $this->assertSame('Brak przypisanych produktów', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame('abc123', $exception->getRequestId());
        $this->assertSame('/api/v1/external/transaction/create-offer', $exception->getPath());
    }

    #[Test]
    public function test_api_exception_from_response_with_missing_fields(): void
    {
        $response = ['error' => 'Some error'];

        $exception = ApiException::fromResponse($response);

        $this->assertSame('Some error', $exception->getMessage());
        $this->assertNull($exception->getRequestId());
        $this->assertNull($exception->getPath());
    }

    #[Test]
    public function test_api_exception_connection_error(): void
    {
        $previous = new Exception('Connection refused');
        $exception = ApiException::connectionError('Connection refused', $previous);

        $this->assertSame('Connection error: Connection refused', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function test_api_exception_timeout(): void
    {
        $exception = ApiException::timeout();

        $this->assertStringContainsString('timed out', $exception->getMessage());
        $this->assertSame(408, $exception->getCode());
    }

    #[Test]
    public function test_all_exceptions_extend_tubapay_exception(): void
    {
        $this->assertInstanceOf(TubaPayException::class, AuthenticationException::invalidCredentials());
        $this->assertInstanceOf(TubaPayException::class, ValidationException::missingField('test'));
        $this->assertInstanceOf(TubaPayException::class, WebhookVerificationException::invalidSignature());
        $this->assertInstanceOf(TubaPayException::class, ApiException::timeout());
    }
}
