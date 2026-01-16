<?php

declare(strict_types=1);

namespace Core45\TubaPay\Exception;

/**
 * Exception thrown when request validation fails.
 *
 * This can happen when:
 * - Required fields are missing
 * - Field values are invalid
 * - Amount is outside allowed range
 */
class ValidationException extends TubaPayException
{
    /**
     * @param array<string, string> $errors Validation errors keyed by field name
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
    ) {
        parent::__construct(
            $message,
            400,
            context: ['errors' => $errors]
        );
    }

    /**
     * Get validation errors keyed by field name.
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Create exception for missing required field.
     */
    public static function missingField(string $fieldName): self
    {
        return new self(
            sprintf('Validation failed: Field "%s" is required.', $fieldName),
            [$fieldName => 'This field is required.']
        );
    }

    /**
     * Create exception for invalid field value.
     */
    public static function invalidField(string $fieldName, string $reason): self
    {
        return new self(
            sprintf('Validation failed: Field "%s" is invalid. %s', $fieldName, $reason),
            [$fieldName => $reason]
        );
    }

    /**
     * Create exception for amount out of range.
     */
    public static function amountOutOfRange(float $amount, float $min, float $max): self
    {
        return new self(
            sprintf(
                'Validation failed: Amount %.2f is outside allowed range (%.2f - %.2f).',
                $amount,
                $min,
                $max
            ),
            ['totalValue' => sprintf('Amount must be between %.2f and %.2f.', $min, $max)]
        );
    }

    /**
     * Create exception for no available products.
     */
    public static function noProductsAvailable(): self
    {
        return new self(
            'Validation failed: No installment products available for this amount.',
            ['totalValue' => 'No products available for this transaction amount.']
        );
    }
}
