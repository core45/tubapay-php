<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\Enum;

use Core45\TubaPay\Enum\AgreementStatus;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AgreementStatusTest extends TestCase
{
    #[Test]
    public function test_agreement_status_has_all_ten_cases(): void
    {
        $cases = AgreementStatus::cases();

        $this->assertCount(10, $cases);
    }

    #[Test]
    public function test_all_status_values(): void
    {
        $this->assertSame('draft', AgreementStatus::Draft->value);
        $this->assertSame('registered', AgreementStatus::Registered->value);
        $this->assertSame('signed', AgreementStatus::Signed->value);
        $this->assertSame('accepted', AgreementStatus::Accepted->value);
        $this->assertSame('rejected', AgreementStatus::Rejected->value);
        $this->assertSame('canceled', AgreementStatus::Canceled->value);
        $this->assertSame('terminated', AgreementStatus::Terminated->value);
        $this->assertSame('withdrew', AgreementStatus::Withdrew->value);
        $this->assertSame('repaid', AgreementStatus::Repaid->value);
        $this->assertSame('closed', AgreementStatus::Closed->value);
    }

    #[Test]
    #[DataProvider('pendingStatusesProvider')]
    public function test_is_pending_returns_true_for_pending_statuses(AgreementStatus $status): void
    {
        $this->assertTrue($status->isPending());
    }

    /**
     * @return array<string, array<int, AgreementStatus>>
     */
    public static function pendingStatusesProvider(): array
    {
        return [
            'draft' => [AgreementStatus::Draft],
            'registered' => [AgreementStatus::Registered],
            'signed' => [AgreementStatus::Signed],
        ];
    }

    #[Test]
    #[DataProvider('nonPendingStatusesProvider')]
    public function test_is_pending_returns_false_for_non_pending_statuses(AgreementStatus $status): void
    {
        $this->assertFalse($status->isPending());
    }

    /**
     * @return array<string, array<int, AgreementStatus>>
     */
    public static function nonPendingStatusesProvider(): array
    {
        return [
            'accepted' => [AgreementStatus::Accepted],
            'rejected' => [AgreementStatus::Rejected],
            'canceled' => [AgreementStatus::Canceled],
            'terminated' => [AgreementStatus::Terminated],
            'withdrew' => [AgreementStatus::Withdrew],
            'repaid' => [AgreementStatus::Repaid],
            'closed' => [AgreementStatus::Closed],
        ];
    }

    #[Test]
    #[DataProvider('successfulStatusesProvider')]
    public function test_is_successful_returns_true_for_successful_statuses(AgreementStatus $status): void
    {
        $this->assertTrue($status->isSuccessful());
    }

    /**
     * @return array<string, array<int, AgreementStatus>>
     */
    public static function successfulStatusesProvider(): array
    {
        return [
            'accepted' => [AgreementStatus::Accepted],
            'repaid' => [AgreementStatus::Repaid],
            'closed' => [AgreementStatus::Closed],
        ];
    }

    #[Test]
    #[DataProvider('failedStatusesProvider')]
    public function test_is_failed_returns_true_for_failed_statuses(AgreementStatus $status): void
    {
        $this->assertTrue($status->isFailed());
    }

    /**
     * @return array<string, array<int, AgreementStatus>>
     */
    public static function failedStatusesProvider(): array
    {
        return [
            'rejected' => [AgreementStatus::Rejected],
            'canceled' => [AgreementStatus::Canceled],
            'terminated' => [AgreementStatus::Terminated],
            'withdrew' => [AgreementStatus::Withdrew],
        ];
    }

    #[Test]
    #[DataProvider('finalStatusesProvider')]
    public function test_is_final_returns_true_for_final_statuses(AgreementStatus $status): void
    {
        $this->assertTrue($status->isFinal());
    }

    /**
     * @return array<string, array<int, AgreementStatus>>
     */
    public static function finalStatusesProvider(): array
    {
        return [
            'rejected' => [AgreementStatus::Rejected],
            'canceled' => [AgreementStatus::Canceled],
            'terminated' => [AgreementStatus::Terminated],
            'withdrew' => [AgreementStatus::Withdrew],
            'closed' => [AgreementStatus::Closed],
        ];
    }

    #[Test]
    public function test_accepted_is_not_final_but_will_be_paid(): void
    {
        $status = AgreementStatus::Accepted;

        $this->assertFalse($status->isFinal());
        $this->assertTrue($status->willBePaid());
    }

    #[Test]
    #[DataProvider('statusLabelsProvider')]
    public function test_get_label_returns_expected_label(AgreementStatus $status, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $status->getLabel());
    }

    /**
     * @return array<string, array<int, AgreementStatus|string>>
     */
    public static function statusLabelsProvider(): array
    {
        return [
            'draft' => [AgreementStatus::Draft, 'Draft'],
            'registered' => [AgreementStatus::Registered, 'Registered'],
            'signed' => [AgreementStatus::Signed, 'Signed'],
            'accepted' => [AgreementStatus::Accepted, 'Accepted'],
            'rejected' => [AgreementStatus::Rejected, 'Rejected'],
            'canceled' => [AgreementStatus::Canceled, 'Canceled'],
            'terminated' => [AgreementStatus::Terminated, 'Terminated'],
            'withdrew' => [AgreementStatus::Withdrew, 'Withdrawn'],
            'repaid' => [AgreementStatus::Repaid, 'Repaid'],
            'closed' => [AgreementStatus::Closed, 'Closed'],
        ];
    }

    #[Test]
    public function test_from_string_creates_correct_status(): void
    {
        $this->assertSame(AgreementStatus::Accepted, AgreementStatus::fromString('accepted'));
        $this->assertSame(AgreementStatus::Accepted, AgreementStatus::fromString('ACCEPTED'));
        $this->assertSame(AgreementStatus::Accepted, AgreementStatus::fromString(' accepted '));
    }

    #[Test]
    public function test_from_string_handles_terminated_by_system(): void
    {
        $this->assertSame(
            AgreementStatus::Terminated,
            AgreementStatus::fromString('terminatedBySystem')
        );
    }

    #[Test]
    public function test_from_string_throws_for_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid agreement status: "invalid"');

        AgreementStatus::fromString('invalid');
    }

    #[Test]
    #[DataProvider('paymentStatusesProvider')]
    public function test_will_be_paid_returns_expected_result(AgreementStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->willBePaid());
    }

    /**
     * @return array<string, array<int, AgreementStatus|bool>>
     */
    public static function paymentStatusesProvider(): array
    {
        return [
            'draft' => [AgreementStatus::Draft, false],
            'registered' => [AgreementStatus::Registered, false],
            'signed' => [AgreementStatus::Signed, false],
            'accepted' => [AgreementStatus::Accepted, true],
            'rejected' => [AgreementStatus::Rejected, false],
            'canceled' => [AgreementStatus::Canceled, false],
            'terminated' => [AgreementStatus::Terminated, false],
            'withdrew' => [AgreementStatus::Withdrew, false],
            'repaid' => [AgreementStatus::Repaid, true],
            'closed' => [AgreementStatus::Closed, true],
        ];
    }
}
