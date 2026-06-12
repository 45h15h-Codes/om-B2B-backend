<?php

namespace Tests\Feature;

use App\Models\Diamond;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontDiamondListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed some test diamonds
        // Diamond A: Round, size 1.00, color D, clarity VVS1, cut Excellent, price 4000
        $diaA = Diamond::create([
            'stock_no' => 'DIA-A',
            'shape' => 'Round',
            'size' => 1.000,
            'color' => 'D',
            'clarity' => 'VVS1',
            'asking_price' => 4000.00,
            'cash_price' => 3900.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'cut' => 'Excellent',
                'diamond_image' => '/images/dia-a.png'
            ]
        ]);
        $diaA->created_at = now()->subMinutes(10);
        $diaA->save();

        // Diamond B: Pear, size 1.50, color E, clarity VS1, cut Very Good, price 6000
        $diaB = Diamond::create([
            'stock_no' => 'DIA-B',
            'shape' => 'Pear',
            'size' => 1.500,
            'color' => 'E',
            'clarity' => 'VS1',
            'asking_price' => 6000.00,
            'cash_price' => 5800.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'cut' => 'Very Good',
                'diamond_image' => '/images/dia-b.png'
            ]
        ]);
        $diaB->created_at = now()->subMinutes(5);
        $diaB->save();

        // Diamond C: Princess, size 2.00, color F, clarity VS2, cut Good, price 8000
        $diaC = Diamond::create([
            'stock_no' => 'DIA-C',
            'shape' => 'Princess',
            'size' => 2.000,
            'color' => 'F',
            'clarity' => 'VS2',
            'asking_price' => 8000.00,
            'cash_price' => 7700.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'cut' => 'Good',
                'diamond_image' => '/images/dia-c.png'
            ]
        ]);
        $diaC->created_at = now();
        $diaC->save();

        // Diamond D: Pending approval (Should be excluded)
        Diamond::create([
            'stock_no' => 'DIA-D-PENDING',
            'shape' => 'Round',
            'size' => 1.250,
            'color' => 'D',
            'clarity' => 'VVS1',
            'asking_price' => 5000.00,
            'status' => Diamond::STATUS_PENDING,
            'inventory_status' => 'available',
            'specifications' => ['cut' => 'Excellent']
        ]);

        // Diamond E: On hold (Should be excluded)
        Diamond::create([
            'stock_no' => 'DIA-E-HOLD',
            'shape' => 'Round',
            'size' => 1.250,
            'color' => 'D',
            'clarity' => 'VVS1',
            'asking_price' => 5000.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'on_hold',
            'specifications' => ['cut' => 'Excellent']
        ]);

        // Diamond F: Sold (Should be excluded)
        Diamond::create([
            'stock_no' => 'DIA-F-SOLD',
            'shape' => 'Round',
            'size' => 1.250,
            'color' => 'D',
            'clarity' => 'VVS1',
            'asking_price' => 5000.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'sold',
            'specifications' => ['cut' => 'Excellent']
        ]);
    }

    /**
     * Test endpoint is public, returns 200 and matches the response contract.
     */
    public function test_storefront_listing_endpoint_returns_json_structure()
    {
        $response = $this->getJson('/api/storefront/diamonds');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'sku',
                    'title',
                    'shape',
                    'carat',
                    'color',
                    'clarity',
                    'cut',
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
        // Assert only approved and available diamonds A, B, and C are returned (3 total)
        $this->assertCount(3, $data);
        
        $skus = collect($data)->pluck('sku')->all();
        $this->assertContains('DIA-A', $skus);
        $this->assertContains('DIA-B', $skus);
        $this->assertContains('DIA-C', $skus);
        $this->assertNotContains('DIA-D-PENDING', $skus);
        $this->assertNotContains('DIA-E-HOLD', $skus);
        $this->assertNotContains('DIA-F-SOLD', $skus);

        // Assert response values map correctly
        $itemA = collect($data)->firstWhere('sku', 'DIA-A');
        $this->assertEquals('1 Carat Round Diamond', $itemA['title']);
        $this->assertEquals(1.0, $itemA['carat']);
        $this->assertEquals(4000.0, $itemA['price']);
        $this->assertEquals('Excellent', $itemA['cut']);
        $this->assertTrue($itemA['availability']);
    }

    /**
     * Test single value filters.
     */
    public function test_storefront_listing_filtering_single_values()
    {
        // Shape filter
        $response = $this->getJson('/api/storefront/diamonds?shape=Round');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('DIA-A', $response->json('data.0.sku'));

        // Color filter
        $response = $this->getJson('/api/storefront/diamonds?color=E');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('DIA-B', $response->json('data.0.sku'));

        // Clarity filter
        $response = $this->getJson('/api/storefront/diamonds?clarity=VS2');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('DIA-C', $response->json('data.0.sku'));

        // Cut filter
        $response = $this->getJson('/api/storefront/diamonds?cut=Very+Good');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('DIA-B', $response->json('data.0.sku'));
    }

    /**
     * Test multi-select filters using arrays and comma-separated values.
     */
    public function test_storefront_listing_filtering_multi_select()
    {
        // 1. Shapes as array
        $response = $this->getJson('/api/storefront/diamonds?shape[]=Round&shape[]=Pear');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertContains('DIA-A', $skus);
        $this->assertContains('DIA-B', $skus);

        // 2. Shapes as comma separated list
        $response = $this->getJson('/api/storefront/diamonds?shape=Round,Princess');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertContains('DIA-A', $skus);
        $this->assertContains('DIA-C', $skus);

        // 3. Cut multi-select as array
        $response = $this->getJson('/api/storefront/diamonds?cut[]=Excellent&cut[]=Good');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertContains('DIA-A', $skus);
        $this->assertContains('DIA-C', $skus);

        // 4. Cut multi-select as comma-separated
        $response = $this->getJson('/api/storefront/diamonds?cut=Very+Good,Good');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertContains('DIA-B', $skus);
        $this->assertContains('DIA-C', $skus);
    }

    /**
     * Test range filters (carat / price).
     */
    public function test_storefront_listing_range_filters()
    {
        // Carat/size range
        $response = $this->getJson('/api/storefront/diamonds?carat_min=1.2&carat_max=1.8');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('DIA-B', $response->json('data.0.sku'));

        // Price range
        $response = $this->getJson('/api/storefront/diamonds?price_min=5000&price_max=9000');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertContains('DIA-B', $skus);
        $this->assertContains('DIA-C', $skus);
    }

    /**
     * Test sorting options.
     */
    public function test_storefront_listing_sorting()
    {
        // price_low_high
        $response = $this->getJson('/api/storefront/diamonds?sort=price_low_high');
        $response->assertStatus(200);
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertEquals(['DIA-A', 'DIA-B', 'DIA-C'], $skus);

        // price_high_low
        $response = $this->getJson('/api/storefront/diamonds?sort=price_high_low');
        $response->assertStatus(200);
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertEquals(['DIA-C', 'DIA-B', 'DIA-A'], $skus);

        // carat_low_high
        $response = $this->getJson('/api/storefront/diamonds?sort=carat_low_high');
        $response->assertStatus(200);
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertEquals(['DIA-A', 'DIA-B', 'DIA-C'], $skus);

        // carat_high_low
        $response = $this->getJson('/api/storefront/diamonds?sort=carat_high_low');
        $response->assertStatus(200);
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertEquals(['DIA-C', 'DIA-B', 'DIA-A'], $skus);

        // newest sorting - DIA-C created last, then B, then A
        $response = $this->getJson('/api/storefront/diamonds?sort=newest');
        $response->assertStatus(200);
        $skus = collect($response->json('data'))->pluck('sku')->all();
        $this->assertEquals(['DIA-C', 'DIA-B', 'DIA-A'], $skus);
    }

    /**
     * Test pagination meta structure.
     */
    public function test_storefront_listing_pagination()
    {
        // Limit page size to 1 to force pagination multiple pages
        $response = $this->getJson('/api/storefront/diamonds?per_page=1&page=2');
        $response->assertStatus(200);
        
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(2, $response->json('pagination.current_page'));
        $this->assertEquals(1, $response->json('pagination.per_page'));
        $this->assertEquals(3, $response->json('pagination.total'));
        $this->assertEquals(3, $response->json('pagination.last_page'));
    }
}
