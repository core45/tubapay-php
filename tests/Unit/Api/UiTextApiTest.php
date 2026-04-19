<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\Api;

use Core45\TubaPay\Api\UiTextApi;
use Core45\TubaPay\DTO\UiTexts;
use Core45\TubaPay\Enum\Environment;
use Core45\TubaPay\Http\InMemoryTokenStorage;
use Core45\TubaPay\Http\TubaPayClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UiTextApiTest extends TestCase
{
    #[Test]
    public function test_get_texts_returns_ui_texts(): void
    {
        $api = $this->createApi(new MockHandler([
            $this->createTokenResponse(),
            new Response(200, [], json_encode([
                'result' => [
                    'response' => [
                        'TP_CHOOSE_RATES_TITLE' => 'Choose installments',
                        'TP_FAST_TRACK_BUTTON' => 'Pay from ${monthlyRateValue}',
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]));

        $texts = $api->getTexts();

        $this->assertInstanceOf(UiTexts::class, $texts);
        $this->assertSame('Choose installments', $texts->get('TP_CHOOSE_RATES_TITLE'));
        $this->assertSame('fallback', $texts->get('UNKNOWN', 'fallback'));
    }

    private function createApi(MockHandler $mockHandler): UiTextApi
    {
        $httpClient = new Client(['handler' => HandlerStack::create($mockHandler)]);

        return new UiTextApi(new TubaPayClient(
            'client-id',
            'client-secret',
            Environment::Test,
            new InMemoryTokenStorage,
            $httpClient,
        ));
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
