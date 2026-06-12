<?php

namespace Tests\Feature;

use App\Models\Jewelery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontJewelleryFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed approved available jewellery items
        Jewelery::create([
            'sku' => 'JEWEL-1',
            'name' => 'Gold Ring',
            'type' => 'Ring',
            'price' => 1000.00,
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'category' => 'Fine Jewelry',
                'metal_type' => 'Gold'
            ]
        ]);

        Jewelery::create([
            'sku' => 'JEWEL-2',
            'name' => 'Platinum Necklace',
            'type' => 'Necklace',
            'price' => 3000.00,
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'category' => 'Bridal',
                'metal_type' => 'Platinum'
            ]
        ]);

        Jewelery::create([
            'sku' => 'JEWEL-3',
            'name' => 'Gold Bracelet',
            'type' => 'Bracelet',
            'price' => 2000.00,
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'category' => 'Fine Jewelry',
                'metal_type' => 'Gold'
            ]
        ]);

        // 2. Seed excluded items (unapproved / unavailable)
        // Pending approval (Should be ignored)
        Jewelery::create([
            'sku' => 'JEWEL-PENDING',
            'name' => 'Pending Ring',
            'type' => 'Ring',
            'price' => 5000.00,
            'status' => Jewelery::STATUS_PENDING,
            'inventory_status' => 'available',
            'specifications' => [
                'category' => 'Custom',
                'metal_type' => 'Silver'
            ]
        ]);

        // Sold (Should be ignored)
        Jewelery::create([
            'sku' => 'JEWEL-SOLD',
            'name' => 'Sold Ring',
            'type' => 'Ring',
            'price' => 500.00,
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'sold',
            'specifications' => [
                'category' => 'Outlet',
                'metal_type' => 'Rose Gold'
            ]
        ]);
    }

    /**
     * Test jewellery filters route returns metadata correctly.
     */
    public function test_jewellery_filters_endpoint_returns_ranges_and_distinct_values()
    {
        $response = $this->getJson('/api/storefront/jewellery/filters');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'types',
                'categories',
                'metals',
                'price_range' => ['min', 'max']
            ]
        ]);

        $data = $response->json('data');

        // Assert distinct values sorted alphabetically
        $this->assertEquals(['Bracelet', 'Necklace', 'Ring'], $data['types']);
        $this->assertEquals(['Bridal', 'Fine Jewelry'], $data['categories']);
        $this->assertEquals(['Gold', 'Platinum'], $data['metals']);

        // Assert ranges: min price = 1000.0, max price = 3000.0
        $this->assertEquals(1000.0, $data['price_range']['min']);
        $this->assertEquals(3000.0, $data['price_range']['max']);
    }
}
