<?php

namespace Tests\Feature;

use App\Models\Jewelery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontJewelleryDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test details of an approved, available jewellery item are returned correctly.
     */
    public function test_detail_endpoint_returns_data_for_approved_available_jewellery()
    {
        $jewellery = Jewelery::create([
            'sku' => 'J-DETAIL-01',
            'name' => 'Premium Diamond Ring',
            'type' => 'Ring',
            'price' => 5000.00,
            'image_url' => '/images/j-legacy.jpg',
            'location' => 'Surat',
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'type_style' => 'Solitaire',
                'category' => 'Fine Jewelry',
                'condition' => 'New',
                'brand' => 'OM Gems',
                'quality' => 'Excellent',
                'metal_type' => 'Platinum',
                'metal_karat' => '950 Plat',
                'total_weight' => 5.2,
                'gemstone_type' => 'Diamond',
                'gemstone_shape' => 'Round',
                'carat_weight' => 1.5,
                'lab' => 'GIA',
                'lab_no' => 'GIA67890',
                'lot_no' => 'LOT-Ring-777',
                'description' => 'A stunning classic diamond engagement ring.',
                'images' => [
                    '/images/j1.jpg',
                    'https://external.com/j2.jpg'
                ]
            ]
        ]);

        $response = $this->getJson("/api/storefront/jewellery/{$jewellery->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'sku',
                'name',
                'type',
                'type_style',
                'category',
                'condition',
                'brand',
                'quality',
                'metal_type',
                'metal_karat',
                'total_weight',
                'gemstone_type',
                'gemstone_shape',
                'carat_weight',
                'lab',
                'lab_no',
                'lot_no',
                'description',
                'price',
                'images',
                'availability',
                'created_at'
            ]
        ]);

        $data = $response->json('data');

        $this->assertEquals($jewellery->id, $data['id']);
        $this->assertEquals('J-DETAIL-01', $data['sku']);
        $this->assertEquals('Premium Diamond Ring', $data['name']);
        $this->assertEquals('Ring', $data['type']);
        $this->assertEquals('Solitaire', $data['type_style']);
        $this->assertEquals('Fine Jewelry', $data['category']);
        $this->assertEquals('New', $data['condition']);
        $this->assertEquals('OM Gems', $data['brand']);
        $this->assertEquals('Excellent', $data['quality']);
        
        $this->assertEquals('Platinum', $data['metal_type']);
        $this->assertEquals('950 Plat', $data['metal_karat']);
        $this->assertEquals(5.2, $data['total_weight']);
        $this->assertEquals('Diamond', $data['gemstone_type']);
        $this->assertEquals('Round', $data['gemstone_shape']);
        $this->assertEquals(1.5, $data['carat_weight']);
        
        $this->assertEquals('GIA', $data['lab']);
        $this->assertEquals('GIA67890', $data['lab_no']);
        $this->assertEquals('LOT-Ring-777', $data['lot_no']);
        $this->assertEquals('A stunning classic diamond engagement ring.', $data['description']);
        $this->assertEquals(5000.0, $data['price']);
        
        $this->assertTrue($data['availability']);
        $this->assertCount(2, $data['images']);
        $this->assertEquals(asset('/images/j1.jpg'), $data['images'][0]);
        $this->assertEquals('https://external.com/j2.jpg', $data['images'][1]);

        $this->assertNotNull($data['created_at']);
        $this->assertStringEndsWith('Z', $data['created_at']);
    }

    /**
     * Test details endpoint returns 404 for unapproved item.
     */
    public function test_detail_endpoint_returns_404_for_unapproved_jewellery()
    {
        $pending = Jewelery::create([
            'sku' => 'JEWEL-PENDING',
            'status' => Jewelery::STATUS_PENDING,
            'inventory_status' => 'available',
        ]);

        $rejected = Jewelery::create([
            'sku' => 'JEWEL-REJECTED',
            'status' => Jewelery::STATUS_REJECTED,
            'inventory_status' => 'available',
        ]);

        $this->getJson("/api/storefront/jewellery/{$pending->id}")->assertStatus(404);
        $this->getJson("/api/storefront/jewellery/{$rejected->id}")->assertStatus(404);
    }

    /**
     * Test details endpoint returns 404 for unavailable item.
     */
    public function test_detail_endpoint_returns_404_for_unavailable_jewellery()
    {
        $hold = Jewelery::create([
            'sku' => 'JEWEL-HOLD',
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'on_hold',
        ]);

        $sold = Jewelery::create([
            'sku' => 'JEWEL-SOLD',
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'sold',
        ]);

        $this->getJson("/api/storefront/jewellery/{$hold->id}")->assertStatus(404);
        $this->getJson("/api/storefront/jewellery/{$sold->id}")->assertStatus(404);
    }
}
