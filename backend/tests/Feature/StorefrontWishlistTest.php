<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerWishlist;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StorefrontWishlistTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customerA;
    private Customer $customerB;
    private User $admin;
    private Diamond $diamond;
    private Jewelery $jewellery;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed customers
        $this->customerA = Customer::create([
            'name' => 'Customer A',
            'email' => 'customer_a@omgems.com',
            'password' => bcrypt('password'),
            'status' => 'active'
        ]);

        $this->customerB = Customer::create([
            'name' => 'Customer B',
            'email' => 'customer_b@omgems.com',
            'password' => bcrypt('password'),
            'status' => 'active'
        ]);

        // 2. Seed admin user
        $this->admin = User::create([
            'name' => 'Wishlist Admin',
            'email' => 'admin_wish@omgems.com',
            'password' => bcrypt('password'),
            'role' => 'normal_admin'
        ]);

        // 3. Seed products
        $this->diamond = Diamond::create([
            'stock_no' => 'DIA-WISH-1',
            'shape' => 'Round',
            'size' => 1.500,
            'color' => 'D',
            'clarity' => 'VVS1',
            'asking_price' => 5000.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'cut' => 'Excellent',
                'diamond_image' => '/images/wish-dia.png'
            ]
        ]);

        $this->jewellery = Jewelery::create([
            'sku' => 'JEWEL-WISH-1',
            'name' => 'Wish Ring',
            'type' => 'Ring',
            'price' => 1200.00,
            'image_url' => '/images/wish-ring.png',
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'category' => 'Fine Jewelry',
                'metal_type' => 'Gold'
            ]
        ]);
    }

    /**
     * Test customers can add items to their wishlist.
     */
    public function test_customer_can_add_item_to_wishlist()
    {
        Sanctum::actingAs($this->customerA);

        $response = $this->postJson('/api/storefront/wishlist', [
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Added to wishlist'
        ]);

        $this->assertDatabaseHas('customer_wishlists', [
            'customer_id' => $this->customerA->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ]);
    }

    /**
     * Test duplicate wishlist entries are prevented.
     */
    public function test_duplicate_wishlist_entries_are_prevented()
    {
        Sanctum::actingAs($this->customerA);

        // Add first time
        $this->postJson('/api/storefront/wishlist', [
            'product_type' => 'jewellery',
            'product_id' => $this->jewellery->id
        ])->assertStatus(200);

        // Add second time
        $response = $this->postJson('/api/storefront/wishlist', [
            'product_type' => 'jewellery',
            'product_id' => $this->jewellery->id
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Product is already in your wishlist.'
        ]);
    }

    /**
     * Test invalid or non-existent products are blocked.
     */
    public function test_invalid_product_additions_are_blocked()
    {
        Sanctum::actingAs($this->customerA);

        $response = $this->postJson('/api/storefront/wishlist', [
            'product_type' => 'diamond',
            'product_id' => 99999 // Invalid ID
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Product does not exist.'
        ]);
    }

    /**
     * Test listing customer wishlist.
     */
    public function test_customer_can_list_wishlist_items()
    {
        // Setup wishlist items for customer A
        CustomerWishlist::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ]);

        CustomerWishlist::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'jewellery',
            'product_id' => $this->jewellery->id
        ]);

        Sanctum::actingAs($this->customerA);

        $response = $this->getJson('/api/storefront/wishlist');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'product_type',
                    'product_id',
                    'title',
                    'image',
                    'price',
                    'availability'
                ]
            ]
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Assert dynamic data resolution
        $diamondItem = collect($data)->firstWhere('product_type', 'diamond');
        $this->assertEquals('1.5 Carat Round Diamond', $diamondItem['title']);
        $this->assertEquals(asset('/images/wish-dia.png'), $diamondItem['image']);
        $this->assertEquals(5000.0, $diamondItem['price']);
        $this->assertTrue($diamondItem['availability']);

        $jewelItem = collect($data)->firstWhere('product_type', 'jewellery');
        $this->assertEquals('Wish Ring', $jewelItem['title']);
        $this->assertEquals(asset('/images/wish-ring.png'), $jewelItem['image']);
        $this->assertEquals(1200.0, $jewelItem['price']);
        $this->assertTrue($jewelItem['availability']);
    }

    /**
     * Test count endpoint works.
     */
    public function test_wishlist_count_endpoint()
    {
        Sanctum::actingAs($this->customerA);

        // Initially 0
        $this->getJson('/api/storefront/wishlist/count')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'count' => 0]);

        // Add an item
        CustomerWishlist::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ]);

        // Now 1
        $this->getJson('/api/storefront/wishlist/count')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'count' => 1]);
    }

    /**
     * Test user can delete their own item.
     */
    public function test_customer_can_delete_own_wishlist_item()
    {
        $item = CustomerWishlist::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ]);

        Sanctum::actingAs($this->customerA);

        $response = $this->deleteJson("/api/storefront/wishlist/{$item->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Removed from wishlist'
        ]);

        $this->assertDatabaseMissing('customer_wishlists', ['id' => $item->id]);
    }

    /**
     * Test customer cannot delete another customer's wishlist item.
     */
    public function test_customer_cannot_delete_other_customer_wishlist_item()
    {
        $item = CustomerWishlist::create([
            'customer_id' => $this->customerB->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ]);

        Sanctum::actingAs($this->customerA);

        $response = $this->deleteJson("/api/storefront/wishlist/{$item->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('customer_wishlists', ['id' => $item->id]);
    }

    /**
     * Test guest unauthenticated access receives 401.
     */
    public function test_guest_access_is_unauthorized()
    {
        $this->getJson('/api/storefront/wishlist')->assertStatus(401);
        $this->postJson('/api/storefront/wishlist', [])->assertStatus(401);
        $this->getJson('/api/storefront/wishlist/count')->assertStatus(401);
        $this->deleteJson('/api/storefront/wishlist/1')->assertStatus(401);
    }

    /**
     * Test admin user access is forbidden.
     */
    public function test_admin_access_is_forbidden()
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/storefront/wishlist')->assertStatus(403);
        $this->postJson('/api/storefront/wishlist', [
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ])->assertStatus(403);
        $this->getJson('/api/storefront/wishlist/count')->assertStatus(403);
        
        $item = CustomerWishlist::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ]);
        $this->deleteJson("/api/storefront/wishlist/{$item->id}")->assertStatus(403);
    }
}
