<?php

declare(strict_types=1);

namespace Core45\TubaPay\Enum;

/**
 * Agreement (transaction) status values from TubaPay.
 *
 * The agreement goes through various states during its lifecycle:
 * - Initial states: Draft -> Registered -> Signed
 * - Final positive: Accepted -> Repaid -> Closed
 * - Final negative: Rejected, Canceled, Terminated, Withdrew
 */
enum AgreementStatus: string
{
    /** Draft - agreement draft (currently unused by TubaPay). */
    case Draft = 'draft';

    /** Registered - agreement is registered in TubaPay (not verified, not started). */
    case Registered = 'registered';

    /** Signed - agreement is signed by customer (not verified, not started). */
    case Signed = 'signed';

    /** Accepted - verified, signed by customer and TubaPay, payment ordered to partner. */
    case Accepted = 'accepted';

    /** Rejected - customer verification failed (will NOT be paid). */
    case Rejected = 'rejected';

    /** Canceled - customer cancelled before signing. */
    case Canceled = 'canceled';

    /** Terminated - TubaPay terminated the agreement. */
    case Terminated = 'terminated';

    /** Withdrew - customer withdrew from agreement. */
    case Withdrew = 'withdrew';

    /** Repaid - customer repaid the agreement in full. */
    case Repaid = 'repaid';

    /** Closed - agreement settled and closed. */
    case Closed = 'closed';

    /**
     * Check if this status indicates a pending state (waiting for customer action or verification).
     */
    public function isPending(): bool
    {
        return in_array($this, [
            self::Draft,
            self::Registered,
            self::Signed,
        ], true);
    }

    /**
     * Check if this status indicates a successful/positive outcome.
     */
    public function isSuccessful(): bool
    {
        return in_array($this, [
            self::Accepted,
            self::Repaid,
            self::Closed,
        ], true);
    }

    /**
     * Check if this status indicates a failed/negative outcome.
     */
    public function isFailed(): bool
    {
        return in_array($this, [
            self::Rejected,
            self::Canceled,
            self::Terminated,
            self::Withdrew,
        ], true);
    }

    /**
     * Check if this is a final status (no more changes expected).
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::Rejected,
            self::Canceled,
            self::Terminated,
            self::Withdrew,
            self::Closed,
        ], true);
    }

    /**
     * Check if the partner will receive payment for this status.
     */
    public function willBePaid(): bool
    {
        return in_array($this, [
            self::Accepted,
            self::Repaid,
            self::Closed,
        ], true);
    }

    /**
     * Get a human-readable label for this status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Registered => 'Registered',
            self::Signed => 'Signed',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Canceled => 'Canceled',
            self::Terminated => 'Terminated',
            self::Withdrew => 'Withdrawn',
            self::Repaid => 'Repaid',
            self::Closed => 'Closed',
        };
    }

    /**
     * Create from string value (case-insensitive).
     */
    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        // Handle alternative spelling
        if ($normalized === 'terminatedbysystem') {
            $normalized = 'terminated';
        }

        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Invalid agreement status: "%s".', $value)
        );
    }
}
