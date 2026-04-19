<?php

declare(strict_types=1);

namespace Core45\TubaPay\Api;

use Core45\TubaPay\DTO\UiTexts;
use Core45\TubaPay\Exception\ApiException;
use Core45\TubaPay\Exception\AuthenticationException;
use Core45\TubaPay\Http\TubaPayClient;

/**
 * API for TubaPay UI labels used by checkout integrations.
 */
final class UiTextApi
{
    private const UI_TEXTS_PATH = '/api/v1/external/transaction/query/get-texts-for-ui-elements';

    public function __construct(
        private readonly TubaPayClient $client,
    ) {}

    /**
     * Fetch all UI text labels.
     *
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function getTexts(): UiTexts
    {
        return UiTexts::fromArray(
            $this->client->get(self::UI_TEXTS_PATH)
        );
    }
}
