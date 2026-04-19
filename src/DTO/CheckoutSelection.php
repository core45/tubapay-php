<?php

declare(strict_types=1);

namespace Core45\TubaPay\DTO;

/**
 * User checkout choices needed to create a TubaPay transaction.
 */
readonly class CheckoutSelection
{
    /**
     * @param  list<string>  $acceptedConsents
     */
    public function __construct(
        public int $installments,
        public array $acceptedConsents = [],
        public ?string $returnUrl = null,
        public ?TransactionMetadata $metadata = null,
    ) {}

    /**
     * Return a copy with transaction metadata attached.
     */
    public function withMetadata(TransactionMetadata $metadata): self
    {
        return new self(
            installments: $this->installments,
            acceptedConsents: $this->acceptedConsents,
            returnUrl: $this->returnUrl,
            metadata: $metadata,
        );
    }
}
