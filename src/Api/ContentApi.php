<?php

declare(strict_types=1);

namespace Core45\TubaPay\Api;

use Core45\TubaPay\DTO\Content\PopupContent;
use Core45\TubaPay\DTO\Content\TopBarContent;
use Core45\TubaPay\Exception\ApiException;
use Core45\TubaPay\Exception\AuthenticationException;
use Core45\TubaPay\Http\TubaPayClient;

/**
 * API for TubaPay hosted promotional content.
 */
final class ContentApi
{
    private const TOP_BAR_PATH = '/api/v1/content/top_bar';

    private const POPUP_PATH = '/api/v1/content/popup';

    public function __construct(
        private readonly TubaPayClient $client,
    ) {}

    /**
     * Fetch top-bar copy.
     *
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function topBar(): TopBarContent
    {
        return TopBarContent::fromArray(
            $this->client->get(self::TOP_BAR_PATH)
        );
    }

    /**
     * Fetch explanatory popup content.
     *
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function popup(): PopupContent
    {
        return PopupContent::fromArray(
            $this->client->get(self::POPUP_PATH)
        );
    }
}
