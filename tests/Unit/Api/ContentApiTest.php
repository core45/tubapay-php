<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\Api;

use Core45\TubaPay\Api\ContentApi;
use Core45\TubaPay\DTO\Content\PopupContent;
use Core45\TubaPay\DTO\Content\TopBarContent;
use Core45\TubaPay\Enum\Environment;
use Core45\TubaPay\Http\InMemoryTokenStorage;
use Core45\TubaPay\Http\TubaPayClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContentApiTest extends TestCase
{
    #[Test]
    public function test_top_bar_returns_content(): void
    {
        $api = $this->createApi(new MockHandler([
            $this->createTokenResponse(),
            new Response(200, [], json_encode([
                'data' => [
                    'main_text' => 'Main text',
                    'button_text' => 'Button',
                    'button_text_mobile' => 'Mobile button',
                ],
            ], JSON_THROW_ON_ERROR)),
        ]));

        $content = $api->topBar();

        $this->assertInstanceOf(TopBarContent::class, $content);
        $this->assertSame('Main text', $content->mainText);
        $this->assertSame('Button', $content->buttonText);
        $this->assertSame('Mobile button', $content->buttonTextMobile);
    }

    #[Test]
    public function test_popup_returns_content(): void
    {
        $api = $this->createApi(new MockHandler([
            $this->createTokenResponse(),
            new Response(200, [], json_encode([
                'data' => [
                    'top_list' => [
                        [
                            'title' => 'Step 1',
                            'description' => 'Choose TubaPay',
                        ],
                    ],
                    'main_text' => 'Popup text',
                ],
            ], JSON_THROW_ON_ERROR)),
        ]));

        $content = $api->popup();

        $this->assertInstanceOf(PopupContent::class, $content);
        $this->assertSame('Popup text', $content->mainText);
        $this->assertCount(1, $content->topList);
        $this->assertSame('Step 1', $content->topList[0]->title);
    }

    private function createApi(MockHandler $mockHandler): ContentApi
    {
        $httpClient = new Client(['handler' => HandlerStack::create($mockHandler)]);

        return new ContentApi(new TubaPayClient(
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
