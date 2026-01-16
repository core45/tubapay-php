<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\DTO;

use Core45\TubaPay\DTO\Transaction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransactionTest extends TestCase
{
    #[Test]
    public function test_constructor_sets_all_properties(): void
    {
        $transaction = new Transaction(
            transactionId: 'aWuiqgk9pWhT65VNvkx',
            transactionLink: 'https://tubapay-test.bacca.pl/s/BveALF0R',
            referenceId: 'f1960ba5-3154-4bfe-824b-ffe9e2e85f66',
        );

        $this->assertSame('aWuiqgk9pWhT65VNvkx', $transaction->transactionId);
        $this->assertSame('https://tubapay-test.bacca.pl/s/BveALF0R', $transaction->transactionLink);
        $this->assertSame('f1960ba5-3154-4bfe-824b-ffe9e2e85f66', $transaction->referenceId);
    }

    #[Test]
    public function test_from_array_parses_api_response(): void
    {
        $data = [
            'result' => [
                'response' => [
                    'referenceId' => 'f1960ba5-3154-4bfe-824b-ffe9e2e85f66',
                    'transaction' => [
                        'transactionLink' => 'https://tubapay-test.bacca.pl/s/BveALF0R',
                        'transactionId' => 'aWuiqgk9pWhT65VNvkx',
                    ],
                ],
            ],
        ];

        $transaction = Transaction::fromArray($data);

        $this->assertSame('aWuiqgk9pWhT65VNvkx', $transaction->transactionId);
        $this->assertSame('https://tubapay-test.bacca.pl/s/BveALF0R', $transaction->transactionLink);
        $this->assertSame('f1960ba5-3154-4bfe-824b-ffe9e2e85f66', $transaction->referenceId);
    }

    #[Test]
    public function test_from_array_handles_flat_structure(): void
    {
        $data = [
            'transactionId' => 'test-id',
            'transactionLink' => 'https://example.com/pay',
            'referenceId' => 'ref-123',
        ];

        $transaction = Transaction::fromArray($data);

        $this->assertSame('test-id', $transaction->transactionId);
        $this->assertSame('https://example.com/pay', $transaction->transactionLink);
    }

    #[Test]
    public function test_from_array_handles_missing_fields(): void
    {
        $transaction = Transaction::fromArray([]);

        $this->assertSame('', $transaction->transactionId);
        $this->assertSame('', $transaction->transactionLink);
        $this->assertSame('', $transaction->referenceId);
    }
}
