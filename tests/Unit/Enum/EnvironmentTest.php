<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\Enum;

use Core45\TubaPay\Enum\Environment;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    #[Test]
    public function test_environment_has_expected_cases(): void
    {
        $cases = Environment::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(Environment::Test, $cases);
        $this->assertContains(Environment::Production, $cases);
    }

    #[Test]
    public function test_environment_values(): void
    {
        $this->assertSame('test', Environment::Test->value);
        $this->assertSame('production', Environment::Production->value);
    }

    #[Test]
    public function test_test_environment_base_url(): void
    {
        $this->assertSame(
            'https://tubapay-test.bacca.pl',
            Environment::Test->getBaseUrl()
        );
    }

    #[Test]
    public function test_production_environment_base_url(): void
    {
        $this->assertSame(
            'https://tubapay.pl',
            Environment::Production->getBaseUrl()
        );
    }

    #[Test]
    #[DataProvider('provideTestEnvironmentStrings')]
    public function test_from_string_returns_test_environment(string $input): void
    {
        $this->assertSame(Environment::Test, Environment::fromString($input));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function provideTestEnvironmentStrings(): array
    {
        return [
            'test' => ['test'],
            'TEST' => ['TEST'],
            'Test' => ['Test'],
            'testing' => ['testing'],
            'sandbox' => ['sandbox'],
            'with spaces' => [' test '],
        ];
    }

    #[Test]
    #[DataProvider('productionEnvironmentStringsProvider')]
    public function test_from_string_returns_production_environment(string $input): void
    {
        $this->assertSame(Environment::Production, Environment::fromString($input));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function productionEnvironmentStringsProvider(): array
    {
        return [
            'production' => ['production'],
            'PRODUCTION' => ['PRODUCTION'],
            'prod' => ['prod'],
            'live' => ['live'],
        ];
    }

    #[Test]
    public function test_from_string_throws_for_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid environment value: "invalid"');

        Environment::fromString('invalid');
    }
}
