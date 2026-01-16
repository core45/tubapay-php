<?php

declare(strict_types=1);

namespace Core45\TubaPay\Exception;

use Throwable;

/**
 * Exception thrown when TubaPay API returns an error.
 */
class ApiException extends TubaPayException
{
    public function __construct(
        string $message,
        int $statusCode = 0,
        ?Throwable $previous = null,
        private readonly ?string $requestId = null,
        private readonly ?string $path = null,
    ) {
        parent::__construct(
            $message,
            $statusCode,
            $previous,
            context: array_filter([
                'request_id' => $requestId,
                'path' => $path,
            ])
        );
    }

    /**
     * Get the API request ID if available.
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Get the API path that was called.
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Create from API error response.
     *
     * Handles both flat format and nested TubaPay format.
     *
     * @param array<string, mixed> $response
     */
    public static function fromResponse(array $response, int $statusCode = 400, ?string $path = null): self
    {
        // Try nested TubaPay format first: result.response.message
        $nestedResponse = $response['result']['response'] ?? [];

        $message = $nestedResponse['message']
            ?? $response['message']
            ?? $response['error']
            ?? 'Unknown API error';

        $requestId = $nestedResponse['requestId']
            ?? $response['requestId']
            ?? null;

        return new self(
            is_string($message) ? $message : 'Unknown API error',
            $statusCode,
            requestId: is_string($requestId) ? $requestId : null,
            path: $path,
        );
    }

    /**
     * Create exception for network/connection errors.
     */
    public static function connectionError(string $message, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Connection error: %s', $message),
            0,
            $previous
        );
    }

    /**
     * Create exception for timeout.
     */
    public static function timeout(): self
    {
        return new self(
            'Request timed out while connecting to TubaPay API.',
            408
        );
    }
}
