<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\DTO;

use Core45\TubaPay\DTO\OfferItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OfferItemTest extends TestCase
{
    #[Test]
    public function test_constructor_with_minimal_data(): void
    {
        $item = new OfferItem(installmentsNumber: 6);

        $this->assertSame(6, $item->installmentsNumber);
        $this->assertNull($item->productId);
        $this->assertNull($item->rrsoPercent);
        $this->assertNull($item->minNetAmount);
        $this->assertNull($item->maxNetAmount);
    }

    #[Test]
    public function test_constructor_with_full_partner_data(): void
    {
        $item = new OfferItem(
            installmentsNumber: 12,
            productId: '12rat_ecomm-abon_c0%_m10.20%_bp',
            rrsoPercent: 0.0,
            userPriceMultiplier: 1.0,
            originCompany: 'BACCA_PAY',
            minNetAmount: 200.0,
            maxNetAmount: 50000.0,
            provisionPercent: 0.85,
            payoffType: 'HYBRID2',
        );

        $this->assertSame(12, $item->installmentsNumber);
        $this->assertSame('12rat_ecomm-abon_c0%_m10.20%_bp', $item->productId);
        $this->assertSame(0.0, $item->rrsoPercent);
        $this->assertSame(1.0, $item->userPriceMultiplier);
        $this->assertSame('BACCA_PAY', $item->originCompany);
        $this->assertSame(200.0, $item->minNetAmount);
        $this->assertSame(50000.0, $item->maxNetAmount);
        $this->assertSame(0.85, $item->provisionPercent);
        $this->assertSame('HYBRID2', $item->payoffType);
    }

    #[Test]
    public function test_from_array_client_type(): void
    {
        $data = ['installmentsNumber' => 6];

        $item = OfferItem::fromArray($data);

        $this->assertSame(6, $item->installmentsNumber);
        $this->assertNull($item->productId);
    }

    #[Test]
    public function test_from_array_partner_type(): void
    {
        $data = [
            'productId' => '6rat_ecomm-abon_c0%_m11.34%_bp',
            'rrsoPercent' => 0,
            'userPriceMultiplier' => 1,
            'installmentsNumber' => 6,
            'originCompany' => 'BACCA_PAY',
            'minNetAmount' => 300,
            'maxNetAmount' => 14000,
            'provisionPercent' => 1.89,
            'payoffType' => 'HYBRID',
        ];

        $item = OfferItem::fromArray($data);

        $this->assertSame(6, $item->installmentsNumber);
        $this->assertSame('6rat_ecomm-abon_c0%_m11.34%_bp', $item->productId);
        $this->assertSame(1.89, $item->provisionPercent);
        $this->assertSame('HYBRID', $item->payoffType);
    }

    #[Test]
    public function test_is_detailed_offer(): void
    {
        $simpleItem = new OfferItem(installmentsNumber: 3);
        $detailedItem = new OfferItem(installmentsNumber: 3, productId: 'test');

        $this->assertFalse($simpleItem->isDetailedOffer());
        $this->assertTrue($detailedItem->isDetailedOffer());
    }

    #[Test]
    public function test_is_amount_in_range_with_limits(): void
    {
        $item = new OfferItem(
            installmentsNumber: 6,
            minNetAmount: 300.0,
            maxNetAmount: 14000.0,
        );

        $this->assertTrue($item->isAmountInRange(300.0));
        $this->assertTrue($item->isAmountInRange(5000.0));
        $this->assertTrue($item->isAmountInRange(14000.0));
        $this->assertFalse($item->isAmountInRange(299.99));
        $this->assertFalse($item->isAmountInRange(14000.01));
    }

    #[Test]
    public function test_is_amount_in_range_without_limits(): void
    {
        $item = new OfferItem(installmentsNumber: 6);

        $this->assertTrue($item->isAmountInRange(1.0));
        $this->assertTrue($item->isAmountInRange(1000000.0));
    }
}
