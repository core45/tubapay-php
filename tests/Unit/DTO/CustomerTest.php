<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\DTO;

use Core45\TubaPay\DTO\Customer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CustomerTest extends TestCase
{
    #[Test]
    public function test_constructor_sets_all_properties(): void
    {
        $customer = new Customer(
            firstName: 'Jan',
            lastName: 'Kowalski',
            email: 'jan@example.com',
            phone: '519088975',
            street: 'Testowa',
            zipCode: '00-001',
            town: 'Warszawa',
            streetNumber: '1',
            flatNumber: '2A',
        );

        $this->assertSame('Jan', $customer->firstName);
        $this->assertSame('Kowalski', $customer->lastName);
        $this->assertSame('jan@example.com', $customer->email);
        $this->assertSame('519088975', $customer->phone);
        $this->assertSame('Testowa', $customer->street);
        $this->assertSame('00-001', $customer->zipCode);
        $this->assertSame('Warszawa', $customer->town);
        $this->assertSame('1', $customer->streetNumber);
        $this->assertSame('2A', $customer->flatNumber);
    }

    #[Test]
    public function test_optional_fields_default_to_null(): void
    {
        $customer = new Customer(
            firstName: 'Jan',
            lastName: 'Kowalski',
            email: 'jan@example.com',
            phone: '519088975',
            street: 'Testowa',
            zipCode: '00-001',
            town: 'Warszawa',
        );

        $this->assertNull($customer->streetNumber);
        $this->assertNull($customer->flatNumber);
    }

    #[Test]
    public function test_to_array_returns_correct_structure(): void
    {
        $customer = new Customer(
            firstName: 'Jan',
            lastName: 'Kowalski',
            email: 'jan@example.com',
            phone: '519088975',
            street: 'Testowa',
            zipCode: '00-001',
            town: 'Warszawa',
            streetNumber: '1',
        );

        $array = $customer->toArray();

        $this->assertSame('Jan', $array['firstName']);
        $this->assertSame('Kowalski', $array['lastName']);
        $this->assertSame('jan@example.com', $array['email']);
        $this->assertSame('519088975', $array['phone']);
        $this->assertSame('Testowa', $array['street']);
        $this->assertSame('00-001', $array['zipCode']);
        $this->assertSame('Warszawa', $array['town']);
        $this->assertSame('1', $array['streetNumber']);
        $this->assertArrayNotHasKey('flatNumber', $array);
    }

    #[Test]
    public function test_to_array_excludes_null_values(): void
    {
        $customer = new Customer(
            firstName: 'Jan',
            lastName: 'Kowalski',
            email: 'jan@example.com',
            phone: '519088975',
            street: 'Testowa',
            zipCode: '00-001',
            town: 'Warszawa',
        );

        $array = $customer->toArray();

        $this->assertArrayNotHasKey('streetNumber', $array);
        $this->assertArrayNotHasKey('flatNumber', $array);
    }

    #[Test]
    public function test_from_array_creates_customer(): void
    {
        $data = [
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'email' => 'jan@example.com',
            'phone' => '519088975',
            'street' => 'Testowa',
            'zipCode' => '00-001',
            'town' => 'Warszawa',
            'streetNumber' => '1',
        ];

        $customer = Customer::fromArray($data);

        $this->assertSame('Jan', $customer->firstName);
        $this->assertSame('Kowalski', $customer->lastName);
        $this->assertSame('jan@example.com', $customer->email);
        $this->assertSame('519088975', $customer->phone);
        $this->assertSame('1', $customer->streetNumber);
    }

    #[Test]
    public function test_from_array_handles_webhook_field_names(): void
    {
        $data = [
            'firstName' => 'Jan',
            'surName' => 'Kowalski',
            'email' => 'jan@example.com',
            'cellphone' => '519088975',
            'street' => 'Testowa',
            'zipCode' => '00-001',
            'town' => 'Warszawa',
        ];

        $customer = Customer::fromArray($data);

        $this->assertSame('Kowalski', $customer->lastName);
        $this->assertSame('519088975', $customer->phone);
    }

    #[Test]
    public function test_from_array_handles_missing_fields(): void
    {
        $customer = Customer::fromArray([]);

        $this->assertSame('', $customer->firstName);
        $this->assertSame('', $customer->lastName);
        $this->assertNull($customer->streetNumber);
    }
}
