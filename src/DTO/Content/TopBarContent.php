<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO\Content;

/**
 * Content displayed in the TubaPay top bar.
 */
readonly class TopBarContent
{
    public function __construct(
        public string $mainText,
        public string $buttonText,
        public string $buttonTextMobile,
    ) {}

    /**
     * Create from API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $content = $data['data'] ?? $data['result']['response']['data'] ?? $data['result']['response'] ?? $data;
        $content = is_array($content) ? $content : [];

        return new self(
            mainText: (string) ($content['main_text'] ?? ''),
            buttonText: (string) ($content['button_text'] ?? ''),
            buttonTextMobile: (string) ($content['button_text_mobile'] ?? ''),
        );
    }
}
