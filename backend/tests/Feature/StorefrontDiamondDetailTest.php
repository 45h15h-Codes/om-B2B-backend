<?php

namespace Tests\Feature;

use App\Models\Diamond;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontDiamondDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test details of an approved, available diamond are returned correctly.
     */
    public function test_detail_endpoint_returns_data_for_approved_available_diamond()
    {
        $diamond = Diamond::create([
            'stock_no' => 'DIA-DETAIL-001',
            'shape' => 'Round Brilliant',
            'size' => 1.500,
            'color' => 'D',
            'clarity' => 'VVS1',
            'asking_price' => 4500.00,
            'cash_price' => 4300.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'cut' => 'Excellent',
                'polish' => 'Excellent',
                'symmetry' => 'Excellent',
                'fluorescence' => 'None',
                'lab' => 'GIA',
                'certificate_number' => '123456789',
                'measurements' => '7.35 x 7.31 x 4.55',
                'depth' => 61.8,
                'table' => 57.0,
                'description' => 'Beautiful premium diamond.',
                'images' => [
                    '/images/dia1.jpg',
                    'https://external.com/dia2.jpg'
                ],
                'video' => 'video/dia_video.mp4'
            ]
        ]);

        $response = $this->getJson("/api/storefront/diamonds/{$diamond->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'sku',
                'slug',
                'title',
                'shape',
                'carat',
                'color',
                'clarity',
                'cut',
                'polish',
                'symmetry',
                'fluorescence',
                'certificate',
                'certificate_number',
                'measurements',
                'depth_percentage',
                'table_percentage',
                'price',
                'asking_price',
                'cash_price',
                'availability',
                'description',
                'images',
                'video',
                'created_at'
            ]
        ]);

        $data = $response->json('data');

        $this->assertEquals($diamond->id, $data['id']);
        $this->assertEquals('DIA-DETAIL-001', $data['sku']);
        $this->assertEquals($diamond->id . '-round-brilliant-diamond', $data['slug']);
        $this->assertEquals('1.5 Carat Round Brilliant Diamond', $data['title']);
        $this->assertEquals('Round Brilliant', $data['shape']);
        $this->assertEquals(1.50, $data['carat']);
        $this->assertEquals('D', $data['color']);
        $this->assertEquals('VVS1', $data['clarity']);
        
        $this->assertEquals('Excellent', $data['cut']);
        $this->assertEquals('Excellent', $data['polish']);
        $this->assertEquals('Excellent', $data['symmetry']);
        $this->assertEquals('None', $data['fluorescence']);
        
        $this->assertEquals('GIA', $data['certificate']);
        $this->assertEquals('123456789', $data['certificate_number']);
        $this->assertEquals('7.35 x 7.31 x 4.55', $data['measurements']);
        $this->assertEquals('61.8', $data['depth_percentage']);
        $this->assertEquals('57', $data['table_percentage']);
        
        $this->assertEquals(4500.0, $data['price']);
        $this->assertEquals(4500.0, $data['asking_price']);
        $this->assertEquals(4300.0, $data['cash_price']);
        $this->assertTrue($data['availability']);
        $this->assertEquals('Beautiful premium diamond.', $data['description']);
        
        // Assert image resolution is correct and absolute URLs are returned
        $this->assertCount(2, $data['images']);
        $this->assertEquals(asset('/images/dia1.jpg'), $data['images'][0]);
        $this->assertEquals('https://external.com/dia2.jpg', $data['images'][1]);

        // Assert video path formatting
        $this->assertEquals(asset('video/dia_video.mp4'), $data['video']);
        
        // Assert created_at matches ISO formatting
        $this->assertNotNull($data['created_at']);
        $this->assertStringEndsWith('Z', $data['created_at']);
    }

    /**
     * Test details endpoint returns 404 for unapproved diamond.
     */
    public function test_detail_endpoint_returns_404_for_unapproved_diamond()
    {
        $diamondPending = Diamond::create([
            'stock_no' => 'DIA-PENDING',
            'shape' => 'Round',
            'status' => Diamond::STATUS_PENDING,
            'inventory_status' => 'available',
        ]);

        $diamondRejected = Diamond::create([
            'stock_no' => 'DIA-REJECTED',
            'shape' => 'Round',
            'status' => Diamond::STATUS_REJECTED,
            'inventory_status' => 'available',
        ]);

        $responsePending = $this->getJson("/api/storefront/diamonds/{$diamondPending->id}");
        $responsePending->assertStatus(404);

        $responseRejected = $this->getJson("/api/storefront/diamonds/{$diamondRejected->id}");
        $responseRejected->assertStatus(404);
    }

    /**
     * Test details endpoint returns 404 for unavailable diamond.
     */
    public function test_detail_endpoint_returns_404_for_unavailable_diamond()
    {
        $diamondHold = Diamond::create([
            'stock_no' => 'DIA-HOLD',
            'shape' => 'Round',
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'on_hold',
        ]);

        $diamondSold = Diamond::create([
            'stock_no' => 'DIA-SOLD',
            'shape' => 'Round',
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'sold',
        ]);

        $responseHold = $this->getJson("/api/storefront/diamonds/{$diamondHold->id}");
        $responseHold->assertStatus(404);

        $responseSold = $this->getJson("/api/storefront/diamonds/{$diamondSold->id}");
        $responseSold->assertStatus(404);
    }

    /**
     * Test fallback functionality when specifications keys are missing.
     */
    public function test_detail_endpoint_gracefully_falls_back_to_model_attributes()
    {
        $diamond = Diamond::create([
            'stock_no' => 'DIA-FALLBACK',
            'shape' => 'Oval',
            'size' => 1.250,
            'color' => 'E',
            'clarity' => 'VS1',
            'asking_price' => 5000.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'available',
            // No custom specification details, only base specifications structure if any
            'specifications' => [
                'lab' => 'IGI',
                'report_no' => '9876543'
            ]
        ]);

        $response = $this->getJson("/api/storefront/diamonds/{$diamond->id}");
        $response->assertStatus(200);

        $data = $response->json('data');

        $this->assertEquals('IGI', $data['certificate']);
        $this->assertEquals('9876543', $data['certificate_number']);
        $this->assertNull($data['cut']);
        $this->assertNull($data['polish']);
        $this->assertNull($data['symmetry']);
        $this->assertNull($data['video']);
        $this->assertCount(0, $data['images']);
    }
}
