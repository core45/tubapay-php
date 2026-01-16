<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\DTO;

use Core45\TubaPay\DTO\OrderItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OrderItemTest extends TestCase
{
    #[Test]
    public function test_constructor_sets_all_properties(): void
    {
        $item = new OrderItem(
            name: 'Zamówienie testowe',
            totalValue: 1000.00,
            brand: 'TubaPay',
            description: 'Test description',
            externalRef: 'ORDER-123',
        );

        $this->assertSame('Zamówienie testowe', $item->name);
        $this->assertSame(1000.00, $item->totalValue);
        $this->assertSame('TubaPay', $item->brand);
        $this->assertSame('Test description', $item->description);
        $this->assertSame('ORDER-123', $item->externalRef);
    }

    #[Test]
    public function test_optional_fields_have_defaults(): void
    {
        $item = new OrderItem(
            name: 'Test',
            totalValue: 500.00,
        );

        $this->assertSame('', $item->brand);
        $this->assertSame('', $item->description);
        $this->assertNull($item->externalRef);
    }

    #[Test]
    public function test_to_array_returns_correct_structure(): void
    {
        $item = new OrderItem(
            name: 'Zamówienie testowe',
            totalValue: 1000.00,
            brand: 'TubaPay',
        );

        $array = $item->toArray();

        $this->assertSame('Zamówienie testowe', $array['name']);
        $this->assertSame(1000.00, $array['totalValue']);
        $this->assertSame('TubaPay', $array['brand']);
        $this->assertArrayNotHasKey('description', $array);
        $this->assertArrayNotHasKey('externalRef', $array);
    }

    #[Test]
    public function test_to_array_excludes_empty_strings_and_nulls(): void
    {
        $item = new OrderItem(
            name: 'Test',
            totalValue: 500.00,
        );

        $array = $item->toArray();

        $this->assertArrayNotHasKey('brand', $array);
        $this->assertArrayNotHasKey('description', $array);
        $this->assertArrayNotHasKey('externalRef', $array);
    }

    #[Test]
    public function test_from_array_creates_order_item(): void
    {
        $data = [
            'name' => 'Test Item',
            'totalValue' => 1500.50,
            'brand' => 'TestBrand',
            'description' => 'A description',
            'externalRef' => 'REF-456',
        ];

        $item = OrderItem::fromArray($data);

        $this->assertSame('Test Item', $item->name);
        $this->assertSame(1500.50, $item->totalValue);
        $this->assertSame('TestBrand', $item->brand);
        $this->assertSame('A description', $item->description);
        $this->assertSame('REF-456', $item->externalRef);
    }

    #[Test]
    public function test_from_array_handles_net_value_field(): void
    {
        $data = [
            'name' => 'Test',
            'netValue' => 2000.00,
        ];

        $item = OrderItem::fromArray($data);

        $this->assertSame(2000.00, $item->totalValue);
    }

    #[Test]
    public function test_from_array_handles_missing_fields(): void
    {
        $item = OrderItem::fromArray([]);

        $this->assertSame('', $item->name);
        $this->assertSame(0.0, $item->totalValue);
        $this->assertSame('', $item->brand);
        $this->assertNull($item->externalRef);
    }
}
