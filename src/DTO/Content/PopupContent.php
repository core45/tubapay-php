<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO\Content;

/**
 * Content displayed in the TubaPay explanatory popup.
 */
readonly class PopupContent
{
    /**
     * @param  list<PopupStep>  $topList
     */
    public function __construct(
        public array $topList,
        public string $mainText,
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

        $steps = [];
        foreach (($content['top_list'] ?? []) as $step) {
            if (is_array($step)) {
                $steps[] = PopupStep::fromArray($step);
            }
        }

        return new self(
            topList: $steps,
            mainText: (string) ($content['main_text'] ?? ''),
        );
    }
}
