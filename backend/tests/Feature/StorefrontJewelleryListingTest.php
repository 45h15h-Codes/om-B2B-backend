<?php

namespace Tests\Feature;

use App\Models\Jewelery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontJewelleryListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed test jewellery items
        // Item A: Ring, category Fine Jewelry, metal Gold, price 1000
        $itemA = Jewelery::create([
            'sku' => 'JEWEL-A',
            'name' => 'Gold Diamond Ring',
            'type' => 'Ring',
            'price' => 1000.00,
            'image_url' => '/images/ring.png',
            'location' => 'Surat',
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'category' => 'Fine Jewelry',
                'metal_type' => 'Gold',
                'metal_karat' => '18 KT',
            ]
        ]);
        $itemA->created_at = now()->subMinutes(10);
        $itemA->save();

        // Item B: Necklace, category Bridal, metal Platinum, price 3000
        $itemB = Jewelery::create([
            'sku' => 'JEWEL-B',
            'name' => 'Platinum Necklace',
            'type' => 'Necklace',
            'price' => 3000.00,
            'image_url' => '/images/necklace.png',
            'location' => 'Mumbai',
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'category' => 'Bridal',
                'metal_type' => 'Platinum',
                'metal_karat' => '950 Plat',
            ]
        ]);
        $itemB->created_at = now()->subMinutes(5);
        $itemB->save();

        // Item C: Bracelet, category Fine Jewelry, metal Gold, price 2000
        $itemC = Jewelery::create([
            'sku' => 'JEWEL-C',
            'name' => 'Gold Bracelet',
            'type' => 'Bracelet',
            'price' => 2000.00,
            'image_url' => '/images/bracelet.png',
            'location' => 'Surat',
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'category' => 'Fine Jewelry',
                'metal_type' => 'Gold',
                'metal_karat' => '14 KT',
            ]
        ]);
        $itemC->created_at = now();
        $itemC->save();

        // Item D: Pending status (Should be excluded)
        Jewelery::create([
            'sku' => 'JEWEL-D-PENDING',
            'name' => 'Pending Ring',
            'type' => 'Ring',
            'price' => 1000.00,
            'status' => Jewelery::STATUS_PENDING,
            'inventory_status' => 'available',
        ]);

        // Item E: On hold status (Should be excluded)
        Jewelery::create([
            'sku' => 'JEWEL-E-HOLD',
            'name' => 'Hold Ring',
            'type' => 'Ring',
            'price' => 1000.00,
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'on_hold',
        ]);

        // Item F: Sold status (Should be excluded)
        Jewelery::create([
            'sku' => 'JEWEL-F-SOLD',
            'name' => 'Sold Ring',
            'type' => 'Ring',
            'price' => 1000.00,
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'sold',
        ]);
    }

    /**
     * Test endpoint is public, returns 200 and matches the response contract.
     */
    public function test_storefront_jewellery_listing_returns_json_structure()
    {
        $response = $this->getJson('/api/storefront/jewellery');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'sku',
                    'name',
                    'type',
                    'category',
                    'metal_type',
                    'metal_karat',
                    'price',
                    'image',
                    'availability'
                ]
            ],
            'pagination' => [
                'current_page',
                'per_page',
                'total',
                'last_page'
            ]
        ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);

        $skus = collect($data)->pluck('sku')->all();
        $this->assertContains('JEWEL-A', $skus);
        $this->assertContains('JEWEL-B', $skus);
        $this->assertContains('JEWEL-C', $skus);
        $this->assertNotContains('JEWEL-D-PENDING', $skus);
        $this->assertNotContains('JEWEL-E-HOLD', $skus);
        $this->assertNotContains('JEWEL-F-SOLD', $skus);

        // Assert mapping matches
        $ring = collect($data)->firstWhere('sku', 'JEWEL-A');
        $this->assertEquals('Gold Diamond Ring', $ring['name']);
        $this->assertEquals('Ring', $ring['type']);
        $this->assertEquals('Fine Jewelry', $ring['category']);
        $this->assertEquals('Gold', $ring['metal_type']);
        $this->assertEquals('18 KT', $ring['metal_karat']);
        $this->assertEquals(1000.0, $ring['price']);
        $this->assertEquals(asset('/images/ring.png'), $ring['image']);
        $this->assertTrue($ring['availability']);
    }

    /**
     * Test single and multi-select filtering logic.
     */
    public function test_storefront_jewellery_filtering()
    {
        // 1. Single Type Filter
        $response = $this->getJson('/api/storefront/jewellery?type=Necklace');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('JEWEL-B', $response->json('data.0.sku'));

        // 2. Multi Type Filter as comma separated list
        $response = $this->getJson('/api/storefront/jewellery?type=Ring,Bracelet');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertContains('JEWEL-A', $skus);
        $this->assertContains('JEWEL-C', $skus);

        // 3. Multi Type Filter as array
        $response = $this->getJson('/api/storefront/jewellery?type[]=Ring&type[]=Bracelet');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));

        // 4. Category Filter
        $response = $this->getJson('/api/storefront/jewellery?category=Bridal');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('JEWEL-B', $response->json('data.0.sku'));

        // 5. Metal Filter (resolves specifications.metal_type)
        $response = $this->getJson('/api/storefront/jewellery?metal=Platinum');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('JEWEL-B', $response->json('data.0.sku'));

        // 6. Price range filters
        $response = $this->getJson('/api/storefront/jewellery?price_min=1500&price_max=2500');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('JEWEL-C', $response->json('data.0.sku'));
    }

    /**
     * Test sorting functions.
     */
    public function test_storefront_jewellery_sorting()
    {
        // price_low_high
        $response = $this->getJson('/api/storefront/jewellery?sort=price_low_high');
        $response->assertStatus(200);
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertEquals(['JEWEL-A', 'JEWEL-C', 'JEWEL-B'], $skus);

        // price_high_low
        $response = $this->getJson('/api/storefront/jewellery?sort=price_high_low');
        $response->assertStatus(200);
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertEquals(['JEWEL-B', 'JEWEL-C', 'JEWEL-A'], $skus);

        // newest sorting (Item C is now, B is 5 mins ago, A is 10 mins ago)
        $response = $this->getJson('/api/storefront/jewellery?sort=newest');
        $response->assertStatus(200);
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertEquals(['JEWEL-C', 'JEWEL-B', 'JEWEL-A'], $skus);
    }
}
