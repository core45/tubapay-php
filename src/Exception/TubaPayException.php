<?php

declare(strict_types=1);

namespace Core45\TubaPay\Exception;

use Exception;
use Throwable;

/**
 * Base exception for all TubaPay SDK errors.
 */
class TubaPayException extends Exception
{
    /**
     * @param array<string, mixed> $context Additional context about the error
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        protected readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get additional context about the error.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
