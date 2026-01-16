<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\Http;

use Core45\TubaPay\Enum\Environment;
use Core45\TubaPay\Exception\AuthenticationException;
use Core45\TubaPay\Http\InMemoryTokenStorage;
use Core45\TubaPay\Http\TokenManager;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TokenManagerTest extends TestCase
{
    #[Test]
    public function test_get_access_token_fetches_new_token(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'result' => [
                    'response' => [
                        'accessToken' => 'test-access-token',
                        'expiresIn' => 86400,
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $storage = new InMemoryTokenStorage();
        $manager = new TokenManager(
            $client,
            'client-id',
            'client-secret',
            Environment::Test,
            $storage,
        );

        $token = $manager->getAccessToken();

        $this->assertSame('test-access-token', $token);
        $this->assertTrue($manager->hasValidToken());
    }

    #[Test]
    public function test_get_access_token_returns_cached_token(): void
    {
        // First call returns token
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'result' => [
                    'response' => [
                        'accessToken' => 'cached-token',
                        'expiresIn' => 86400,
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $storage = new InMemoryTokenStorage();
        $manager = new TokenManager(
            $client,
            'client-id',
            'client-secret',
            Environment::Test,
            $storage,
        );

        // First call - fetches from API
        $token1 = $manager->getAccessToken();
        $this->assertSame('cached-token', $token1);

        // Second call - should use cached token (mock queue is empty)
        $token2 = $manager->getAccessToken();
        $this->assertSame('cached-token', $token2);
    }

    #[Test]
    public function test_refresh_token_gets_new_token(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'result' => [
                    'response' => [
                        'accessToken' => 'first-token',
                        'expiresIn' => 86400,
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
            new Response(200, [], json_encode([
                'result' => [
                    'response' => [
                        'accessToken' => 'refreshed-token',
                        'expiresIn' => 86400,
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $manager = new TokenManager(
            $client,
            'client-id',
            'client-secret',
            Environment::Test,
            new InMemoryTokenStorage(),
        );

        $token1 = $manager->getAccessToken();
        $this->assertSame('first-token', $token1);

        $token2 = $manager->refreshToken();
        $this->assertSame('refreshed-token', $token2);
    }

    #[Test]
    public function test_throws_on_missing_credentials(): void
    {
        $mockHandler = new MockHandler([]);
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $manager = new TokenManager(
            $client,
            '',
            '',
            Environment::Test,
            new InMemoryTokenStorage(),
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Client ID and secret are required');

        $manager->getAccessToken();
    }

    #[Test]
    public function test_throws_on_empty_client_id(): void
    {
        $mockHandler = new MockHandler([]);
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $manager = new TokenManager(
            $client,
            '',
            'secret',
            Environment::Test,
            new InMemoryTokenStorage(),
        );

        $this->expectException(AuthenticationException::class);

        $manager->getAccessToken();
    }

    #[Test]
    public function test_throws_on_401_response(): void
    {
        $mockHandler = new MockHandler([
            new Response(401, [], json_encode([
                'error' => 'invalid_client',
                'message' => 'Invalid credentials',
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = new Client([
            'handler' => HandlerStack::create($mockHandler),
            'http_errors' => true,
        ]);
        $manager = new TokenManager(
            $client,
            'wrong-id',
            'wrong-secret',
            Environment::Test,
            new InMemoryTokenStorage(),
        );

        $this->expectException(AuthenticationException::class);
        // The message from the API response is used
        $this->expectExceptionMessage('Invalid credentials');

        $manager->getAccessToken();
    }

    #[Test]
    public function test_throws_on_invalid_response_format(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], 'not json'),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $manager = new TokenManager(
            $client,
            'client-id',
            'client-secret',
            Environment::Test,
            new InMemoryTokenStorage(),
        );

        $this->expectException(AuthenticationException::class);
        // Non-JSON response results in invalid response format message
        $this->expectExceptionMessage('Invalid response format from token endpoint');

        $manager->getAccessToken();
    }

    #[Test]
    public function test_throws_on_missing_token_in_response(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'result' => [
                    'response' => [
                        'expiresIn' => 86400,
                        // Missing accessToken
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $manager = new TokenManager(
            $client,
            'client-id',
            'client-secret',
            Environment::Test,
            new InMemoryTokenStorage(),
        );

        $this->expectException(AuthenticationException::class);
        // Response is valid JSON but missing token
        $this->expectExceptionMessage('No access token');

        $manager->getAccessToken();
    }

    #[Test]
    public function test_clear_token(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'result' => [
                    'response' => [
                        'accessToken' => 'test-token',
                        'expiresIn' => 86400,
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $manager = new TokenManager(
            $client,
            'client-id',
            'client-secret',
            Environment::Test,
            new InMemoryTokenStorage(),
        );

        $manager->getAccessToken();
        $this->assertTrue($manager->hasValidToken());

        $manager->clearToken();
        $this->assertFalse($manager->hasValidToken());
    }

    #[Test]
    public function test_uses_production_environment(): void
    {
        // We can't easily test the URL without inspecting the request,
        // but we can verify it doesn't throw
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'result' => [
                    'response' => [
                        'accessToken' => 'prod-token',
                        'expiresIn' => 86400,
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $manager = new TokenManager(
            $client,
            'client-id',
            'client-secret',
            Environment::Production,
            new InMemoryTokenStorage(),
        );

        $token = $manager->getAccessToken();
        $this->assertSame('prod-token', $token);
    }

    #[Test]
    public function test_handles_flat_response_format(): void
    {
        // Some APIs might return flat structure
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'accessToken' => 'flat-token',
                'expiresIn' => 3600,
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $manager = new TokenManager(
            $client,
            'client-id',
            'client-secret',
            Environment::Test,
            new InMemoryTokenStorage(),
        );

        $token = $manager->getAccessToken();
        $this->assertSame('flat-token', $token);
    }
}
