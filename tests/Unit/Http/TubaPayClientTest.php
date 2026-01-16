<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\Http;

use Core45\TubaPay\Enum\Environment;
use Core45\TubaPay\Exception\ApiException;
use Core45\TubaPay\Exception\AuthenticationException;
use Core45\TubaPay\Http\InMemoryTokenStorage;
use Core45\TubaPay\Http\TubaPayClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TubaPayClientTest extends TestCase
{
    #[Test]
    public function test_get_request(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            new Response(200, [], json_encode([
                'result' => ['response' => ['data' => 'test']],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = $this->createClient($mockHandler);
        $result = $client->get('/api/v1/test');

        $this->assertArrayHasKey('result', $result);
    }

    #[Test]
    public function test_post_request(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            new Response(200, [], json_encode([
                'result' => ['response' => ['created' => true]],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = $this->createClient($mockHandler);
        $result = $client->post('/api/v1/create', ['name' => 'test']);

        $this->assertArrayHasKey('result', $result);
    }

    #[Test]
    public function test_put_request(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            new Response(200, [], json_encode([
                'result' => ['response' => ['updated' => true]],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = $this->createClient($mockHandler);
        $result = $client->put('/api/v1/update', ['name' => 'updated']);

        $this->assertArrayHasKey('result', $result);
    }

    #[Test]
    public function test_delete_request(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            new Response(200, [], json_encode([
                'result' => ['response' => ['deleted' => true]],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = $this->createClient($mockHandler);
        $result = $client->delete('/api/v1/delete');

        $this->assertArrayHasKey('result', $result);
    }

    #[Test]
    public function test_request_includes_authorization_header(): void
    {
        $container = [];
        $history = Middleware::history($container);

        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            new Response(200, [], '{}'),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($history);

        $httpClient = new Client(['handler' => $handlerStack]);
        $storage = new InMemoryTokenStorage();
        $client = new TubaPayClient(
            'client-id',
            'client-secret',
            Environment::Test,
            $storage,
            $httpClient,
        );

        $client->get('/api/v1/test');

        // The second request (after token fetch) should have auth header
        $this->assertCount(2, $container);
        /** @var Request $request */
        $request = $container[1]['request'];
        $this->assertStringStartsWith('Bearer ', $request->getHeader('Authorization')[0]);
    }

    #[Test]
    public function test_throws_api_exception_on_error(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            new Response(400, [], json_encode([
                'result' => [
                    'response' => [
                        'message' => 'Invalid request',
                        'requestId' => 'req-123',
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = $this->createClient($mockHandler);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid request');

        $client->get('/api/v1/bad-request');
    }

    #[Test]
    public function test_throws_authentication_exception_on_401(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            new Response(401, [], json_encode([
                'error' => 'token_expired',
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = $this->createClient($mockHandler);

        $this->expectException(AuthenticationException::class);

        $client->get('/api/v1/protected');
    }

    #[Test]
    public function test_get_token_manager(): void
    {
        $mockHandler = new MockHandler([]);
        $client = $this->createClient($mockHandler);

        $tokenManager = $client->getTokenManager();

        $this->assertNotNull($tokenManager);
    }

    #[Test]
    public function test_get_environment(): void
    {
        $mockHandler = new MockHandler([]);

        $httpClient = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $client = new TubaPayClient(
            'client-id',
            'client-secret',
            Environment::Production,
            null,
            $httpClient,
        );

        $this->assertSame(Environment::Production, $client->getEnvironment());
    }

    #[Test]
    public function test_build_url(): void
    {
        $mockHandler = new MockHandler([]);
        $httpClient = new Client(['handler' => HandlerStack::create($mockHandler)]);

        $testClient = new TubaPayClient(
            'client-id',
            'client-secret',
            Environment::Test,
            null,
            $httpClient,
        );

        $prodClient = new TubaPayClient(
            'client-id',
            'client-secret',
            Environment::Production,
            null,
            $httpClient,
        );

        $this->assertSame(
            'https://tubapay-test.bacca.pl/api/v1/test',
            $testClient->buildUrl('/api/v1/test')
        );

        // Production uses tubapay.pl
        $this->assertSame(
            'https://tubapay.pl/api/v1/test',
            $prodClient->buildUrl('/api/v1/test')
        );
    }

    #[Test]
    public function test_build_url_handles_leading_slash(): void
    {
        $mockHandler = new MockHandler([]);
        $httpClient = new Client(['handler' => HandlerStack::create($mockHandler)]);

        $client = new TubaPayClient(
            'client-id',
            'client-secret',
            Environment::Test,
            null,
            $httpClient,
        );

        // With leading slash
        $this->assertSame(
            'https://tubapay-test.bacca.pl/api/v1/test',
            $client->buildUrl('/api/v1/test')
        );

        // Without leading slash
        $this->assertSame(
            'https://tubapay-test.bacca.pl/api/v1/test',
            $client->buildUrl('api/v1/test')
        );
    }

    #[Test]
    public function test_handles_empty_response(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            new Response(200, [], ''),
        ]);

        $client = $this->createClient($mockHandler);
        $result = $client->get('/api/v1/empty');

        $this->assertSame([], $result);
    }

    #[Test]
    public function test_handles_non_json_response(): void
    {
        $mockHandler = new MockHandler([
            $this->createTokenResponse(),
            new Response(200, [], 'OK'),
        ]);

        $client = $this->createClient($mockHandler);
        $result = $client->get('/api/v1/plain');

        $this->assertSame([], $result);
    }

    private function createClient(MockHandler $mockHandler): TubaPayClient
    {
        $httpClient = new Client(['handler' => HandlerStack::create($mockHandler)]);

        return new TubaPayClient(
            'client-id',
            'client-secret',
            Environment::Test,
            new InMemoryTokenStorage(),
            $httpClient,
        );
    }

    private function createTokenResponse(): Response
    {
        return new Response(200, [], json_encode([
            'result' => [
                'response' => [
                    'accessToken' => 'test-token',
                    'expiresIn' => 86400,
                ],
            ],
        ], JSON_THROW_ON_ERROR));
    }
}
