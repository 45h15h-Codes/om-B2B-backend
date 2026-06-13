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
                'categories' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'image',
                        'products_count',
                    ]
                ],
                'metals',
                'price_range' => ['min', 'max']
            ]
        ]);

        $data = $response->json('data');

        // Assert distinct values sorted alphabetically
        $this->assertEquals(['Bracelet', 'Necklace', 'Ring'], $data['types']);
        
        $categories = $data['categories'];
        $this->assertCount(6, $categories);

        // Assert Ring category
        $this->assertEquals('Ring', $categories[0]['name']);
        $this->assertEquals('ring', $categories[0]['slug']);
        $this->assertEquals(1, $categories[0]['products_count']);

        // Assert Bracelet category
        $this->assertEquals('Bracelet', $categories[1]['name']);
        $this->assertEquals('bracelet', $categories[1]['slug']);
        $this->assertEquals(1, $categories[1]['products_count']);

        // Assert Earings category
        $this->assertEquals('Earings', $categories[2]['name']);
        $this->assertEquals('earings', $categories[2]['slug']);
        $this->assertEquals(0, $categories[2]['products_count']);

        // Assert Necklace category
        $this->assertEquals('Necklace', $categories[3]['name']);
        $this->assertEquals('necklace', $categories[3]['slug']);
        $this->assertEquals(1, $categories[3]['products_count']);

        $this->assertEquals(['Gold', 'Platinum'], $data['metals']);

        // Assert ranges: min price = 1000.0, max price = 3000.0
        $this->assertEquals(1000.0, $data['price_range']['min']);
        $this->assertEquals(3000.0, $data['price_range']['max']);
    }

    /**
     * Test jewellery categories route returns correct format and counts.
     */
    public function test_jewellery_categories_endpoint()
    {
        $response = $this->getJson('/api/storefront/jewellery/categories');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'image',
                    'products_count'
                ]
            ]
        ]);

        $categories = $response->json('data');
        $this->assertCount(6, $categories);

        // Assert Ring
        $this->assertEquals(1, $categories[0]['id']);
        $this->assertEquals('Ring', $categories[0]['name']);
        $this->assertEquals('ring', $categories[0]['slug']);
        $this->assertEquals(1, $categories[0]['products_count']);

        // Assert Bracelet
        $this->assertEquals(2, $categories[1]['id']);
        $this->assertEquals('Bracelet', $categories[1]['name']);
        $this->assertEquals('bracelet', $categories[1]['slug']);
        $this->assertEquals(1, $categories[1]['products_count']);
    }

    /**
     * Test jewellery catalog listing endpoint filters correctly by category slug.
     */
    public function test_jewellery_category_slug_filtering()
    {
        // 1. Filter by single category slug (ring)
        $response = $this->getJson('/api/storefront/jewellery?category=ring');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('JEWEL-1', $data[0]['sku']);

        // 2. Filter by multi category slugs comma separated (ring,necklace)
        $response = $this->getJson('/api/storefront/jewellery?category=ring,necklace');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $skus = collect($data)->pluck('sku')->all();
        $this->assertContains('JEWEL-1', $skus);
        $this->assertContains('JEWEL-2', $skus);

        // 3. Filter by legacy/custom specifications category name (Bridal)
        $response = $this->getJson('/api/storefront/jewellery?category=Bridal');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('JEWEL-2', $data[0]['sku']);

        // 4. Filter by legacy/custom specifications category name case-insensitively (bridal)
        $response = $this->getJson('/api/storefront/jewellery?category=bridal');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('JEWEL-2', $data[0]['sku']);
    }
}
