<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\DTO;

use Core45\TubaPay\DTO\Offer;
use Core45\TubaPay\DTO\OfferItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OfferTest extends TestCase
{
    #[Test]
    public function test_from_array_parses_client_offer_response(): void
    {
        $data = [
            'result' => [
                'response' => [
                    'referenceId' => '8b292a75-fce9-4dae-adc4-d56f4b8413ef',
                    'offer' => [
                        'partnerId' => '703419',
                        'accountId' => '703419',
                        'type' => 'client',
                        'totalValue' => 1000,
                        'offerItems' => [
                            ['installmentsNumber' => 3],
                            ['installmentsNumber' => 6],
                            ['installmentsNumber' => 9],
                            ['installmentsNumber' => 12],
                        ],
                    ],
                ],
            ],
        ];

        $offer = Offer::fromArray($data);

        $this->assertSame('8b292a75-fce9-4dae-adc4-d56f4b8413ef', $offer->referenceId);
        $this->assertSame('703419', $offer->partnerId);
        $this->assertSame('703419', $offer->accountId);
        $this->assertSame('client', $offer->type);
        $this->assertSame(1000.0, $offer->totalValue);
        $this->assertCount(4, $offer->items);
    }

    #[Test]
    public function test_from_array_parses_partner_offer_response(): void
    {
        $data = [
            'result' => [
                'response' => [
                    'referenceId' => 'b48d476b-b9bb-4a1d-a413-76b97e6ae799',
                    'offer' => [
                        'partnerId' => '703419',
                        'accountId' => '703419',
                        'type' => 'partner',
                        'totalValue' => 1000,
                        'offerItems' => [
                            [
                                'productId' => '3raty_ecomm-abon_c0%_m4.05%_bp',
                                'rrsoPercent' => 0,
                                'userPriceMultiplier' => 1,
                                'installmentsNumber' => 3,
                                'originCompany' => 'BACCA_PAY',
                                'minNetAmount' => 200,
                                'maxNetAmount' => 50000,
                                'provisionPercent' => 1.35,
                                'payoffType' => 'HYBRID2',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $offer = Offer::fromArray($data);

        $this->assertSame('partner', $offer->type);
        $this->assertCount(1, $offer->items);
        $this->assertSame('3raty_ecomm-abon_c0%_m4.05%_bp', $offer->items[0]->productId);
        $this->assertSame(1.35, $offer->items[0]->provisionPercent);
    }

    #[Test]
    public function test_get_available_installments(): void
    {
        $offer = new Offer(
            referenceId: 'ref-123',
            partnerId: '703419',
            accountId: '703419',
            type: 'client',
            totalValue: 1000.0,
            items: [
                new OfferItem(installmentsNumber: 3),
                new OfferItem(installmentsNumber: 6),
                new OfferItem(installmentsNumber: 12),
            ],
        );

        $installments = $offer->getAvailableInstallments();

        $this->assertSame([3, 6, 12], $installments);
    }

    #[Test]
    public function test_find_by_installments_returns_correct_item(): void
    {
        $offer = new Offer(
            referenceId: 'ref-123',
            partnerId: '703419',
            accountId: '703419',
            type: 'client',
            totalValue: 1000.0,
            items: [
                new OfferItem(installmentsNumber: 3),
                new OfferItem(installmentsNumber: 6, productId: 'test-6'),
                new OfferItem(installmentsNumber: 12),
            ],
        );

        $item = $offer->findByInstallments(6);

        $this->assertNotNull($item);
        $this->assertSame(6, $item->installmentsNumber);
        $this->assertSame('test-6', $item->productId);
    }

    #[Test]
    public function test_find_by_installments_returns_null_when_not_found(): void
    {
        $offer = new Offer(
            referenceId: 'ref-123',
            partnerId: '703419',
            accountId: '703419',
            type: 'client',
            totalValue: 1000.0,
            items: [
                new OfferItem(installmentsNumber: 3),
            ],
        );

        $item = $offer->findByInstallments(24);

        $this->assertNull($item);
    }

    #[Test]
    public function test_has_installment_option(): void
    {
        $offer = new Offer(
            referenceId: 'ref-123',
            partnerId: '703419',
            accountId: '703419',
            type: 'client',
            totalValue: 1000.0,
            items: [
                new OfferItem(installmentsNumber: 6),
            ],
        );

        $this->assertTrue($offer->hasInstallmentOption(6));
        $this->assertFalse($offer->hasInstallmentOption(12));
    }

    #[Test]
    public function test_is_client_offer(): void
    {
        $clientOffer = new Offer(
            referenceId: 'ref-123',
            partnerId: '703419',
            accountId: '703419',
            type: 'client',
            totalValue: 1000.0,
            items: [],
        );

        $partnerOffer = new Offer(
            referenceId: 'ref-123',
            partnerId: '703419',
            accountId: '703419',
            type: 'partner',
            totalValue: 1000.0,
            items: [],
        );

        $this->assertTrue($clientOffer->isClientOffer());
        $this->assertFalse($clientOffer->isPartnerOffer());
        $this->assertTrue($partnerOffer->isPartnerOffer());
        $this->assertFalse($partnerOffer->isClientOffer());
    }
}
