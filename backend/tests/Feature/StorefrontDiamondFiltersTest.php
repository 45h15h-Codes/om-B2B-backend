<?php

namespace Tests\Feature;

use App\Models\Diamond;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontDiamondFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed approved available diamonds
        Diamond::create([
            'stock_no' => 'DIA-1',
            'shape' => 'Round',
            'size' => 1.000,
            'color' => 'D',
            'clarity' => 'VVS1',
            'asking_price' => 5000.00,
            'cash_price' => 4800.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => ['cut' => 'Excellent']
        ]);

        Diamond::create([
            'stock_no' => 'DIA-2',
            'shape' => 'Oval',
            'size' => 1.500,
            'color' => 'E',
            'clarity' => 'VS1',
            'asking_price' => 8000.00,
            'cash_price' => 7500.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => ['cut' => 'Very Good']
        ]);

        Diamond::create([
            'stock_no' => 'DIA-3',
            'shape' => 'Round',
            'size' => 2.500,
            'color' => 'D',
            'clarity' => 'VS1',
            'asking_price' => 12000.00,
            'cash_price' => 11000.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => ['cut' => 'Excellent']
        ]);

        // 2. Seed excluded diamonds (unapproved / unavailable)
        // Pending approval (Should be ignored)
        Diamond::create([
            'stock_no' => 'DIA-PENDING',
            'shape' => 'Princess',
            'size' => 3.000,
            'color' => 'F',
            'clarity' => 'SI1',
            'asking_price' => 15000.00,
            'status' => Diamond::STATUS_PENDING,
            'inventory_status' => 'available',
            'specifications' => ['cut' => 'Good']
        ]);

        // Sold (Should be ignored)
        Diamond::create([
            'stock_no' => 'DIA-SOLD',
            'shape' => 'Marquise',
            'size' => 0.500,
            'color' => 'G',
            'clarity' => 'I1',
            'asking_price' => 2000.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'sold',
            'specifications' => ['cut' => 'Fair']
        ]);
    }

    /**
     * Test filters route returns metadata correctly.
     */
    public function test_diamond_filters_endpoint_returns_ranges_and_distinct_values()
    {
        $response = $this->getJson('/api/storefront/diamonds/filters');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'shapes',
                'colors',
                'clarities',
                'cuts',
                'carat_range' => ['min', 'max'],
                'price_range' => ['min', 'max']
            ]
        ]);

        $data = $response->json('data');

        // Assert distinct values sorted alphabetically
        $this->assertEquals(['Oval', 'Round'], $data['shapes']);
        $this->assertEquals(['D', 'E'], $data['colors']);
        $this->assertEquals(['VS1', 'VVS1'], $data['clarities']);
        $this->assertEquals(['Excellent', 'Very Good'], $data['cuts']);

        // Assert ranges: min size = 1.0, max size = 2.5
        $this->assertEquals(1.0, $data['carat_range']['min']);
        $this->assertEquals(2.5, $data['carat_range']['max']);

        // Assert price range: min price = 5000.0, max price = 12000.0 (using asking_price)
        $this->assertEquals(5000.0, $data['price_range']['min']);
        $this->assertEquals(12000.0, $data['price_range']['max']);
    }
}
