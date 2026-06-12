<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerCartItem;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StorefrontCartTest extends TestCase
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
            'name' => 'Cart Admin',
            'email' => 'admin_cart@omgems.com',
            'password' => bcrypt('password'),
            'role' => 'normal_admin'
        ]);

        // 3. Seed approved and available products
        $this->diamond = Diamond::create([
            'stock_no' => 'DIA-CART-1',
            'shape' => 'Round',
            'size' => 1.200,
            'color' => 'D',
            'clarity' => 'VVS1',
            'asking_price' => 4500.00,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'cut' => 'Excellent',
                'diamond_image' => '/images/cart-dia.png'
            ]
        ]);

        $this->jewellery = Jewelery::create([
            'sku' => 'JEWEL-CART-1',
            'name' => 'Gold Ring',
            'type' => 'Ring',
            'price' => 1000.00,
            'image_url' => '/images/cart-ring.png',
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'available',
            'specifications' => [
                'category' => 'Fine Jewelry',
                'metal_type' => 'Gold'
            ]
        ]);
    }

    /**
     * 1. Test Customer can add diamond to cart.
     */
    public function test_customer_can_add_diamond_to_cart()
    {
        Sanctum::actingAs($this->customerA);

        $response = $this->postJson('/api/storefront/cart', [
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Added to cart'
        ]);

        $this->assertDatabaseHas('customer_cart_items', [
            'customer_id' => $this->customerA->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id,
            'quantity' => 1
        ]);
    }

    /**
     * 2. Test Customer can add jewellery to cart.
     */
    public function test_customer_can_add_jewellery_to_cart()
    {
        Sanctum::actingAs($this->customerA);

        $response = $this->postJson('/api/storefront/cart', [
            'product_type' => 'jewellery',
            'product_id' => $this->jewellery->id,
            'quantity' => 3
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Added to cart'
        ]);

        $this->assertDatabaseHas('customer_cart_items', [
            'customer_id' => $this->customerA->id,
            'product_type' => 'jewellery',
            'product_id' => $this->jewellery->id,
            'quantity' => 3
        ]);
    }

    /**
     * 3. Test Duplicate diamond is prevented.
     */
    public function test_duplicate_diamond_is_prevented()
    {
        Sanctum::actingAs($this->customerA);

        // Add first time
        $this->postJson('/api/storefront/cart', [
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ])->assertStatus(200);

        // Add second time
        $response = $this->postJson('/api/storefront/cart', [
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Diamond already exists in cart.'
        ]);

        // Assert quantity remains 1
        $this->assertEquals(1, CustomerCartItem::where('customer_id', $this->customerA->id)
            ->where('product_type', 'diamond')
            ->where('product_id', $this->diamond->id)
            ->value('quantity'));
    }

    /**
     * 4. Test Existing jewellery increments quantity.
     */
    public function test_existing_jewellery_increments_quantity()
    {
        Sanctum::actingAs($this->customerA);

        // Add 2 rings
        $this->postJson('/api/storefront/cart', [
            'product_type' => 'jewellery',
            'product_id' => $this->jewellery->id,
            'quantity' => 2
        ])->assertStatus(200);

        // Add 3 more rings
        $response = $this->postJson('/api/storefront/cart', [
            'product_type' => 'jewellery',
            'product_id' => $this->jewellery->id,
            'quantity' => 3
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Cart item quantity updated.'
        ]);

        // Total quantity should be 5
        $this->assertEquals(5, CustomerCartItem::where('customer_id', $this->customerA->id)
            ->where('product_type', 'jewellery')
            ->where('product_id', $this->jewellery->id)
            ->value('quantity'));
    }

    /**
     * 5. Test Invalid product is blocked.
     */
    public function test_invalid_product_is_blocked()
    {
        Sanctum::actingAs($this->customerA);

        $response = $this->postJson('/api/storefront/cart', [
            'product_type' => 'diamond',
            'product_id' => 99999
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Product is not available for purchase.'
        ]);
    }

    /**
     * 6. Test Unavailable products cannot be added.
     */
    public function test_unavailable_products_cannot_be_added()
    {
        Sanctum::actingAs($this->customerA);

        // Unapproved diamond
        $unapprovedDiamond = Diamond::create([
            'stock_no' => 'DIA-UNAPP-1',
            'shape' => 'Ovals',
            'size' => 1.000,
            'color' => 'E',
            'clarity' => 'VVS2',
            'asking_price' => 3000.00,
            'status' => Diamond::STATUS_PENDING,
            'inventory_status' => 'available',
        ]);

        // Sold jewellery
        $soldJewellery = Jewelery::create([
            'sku' => 'JEWEL-SOLD-1',
            'name' => 'Sold Ring',
            'type' => 'Ring',
            'price' => 800.00,
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'sold',
        ]);

        // Try adding unapproved diamond
        $this->postJson('/api/storefront/cart', [
            'product_type' => 'diamond',
            'product_id' => $unapprovedDiamond->id
        ])->assertStatus(422);

        // Try adding sold jewellery
        $this->postJson('/api/storefront/cart', [
            'product_type' => 'jewellery',
            'product_id' => $soldJewellery->id,
            'quantity' => 1
        ])->assertStatus(422);
    }

    /**
     * 7. Test Customer can list cart items.
     */
    public function test_customer_can_list_cart_items()
    {
        // Add items to customer A's cart
        CustomerCartItem::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id,
            'quantity' => 1
        ]);

        CustomerCartItem::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'jewellery',
            'product_id' => $this->jewellery->id,
            'quantity' => 3
        ]);

        Sanctum::actingAs($this->customerA);

        $response = $this->getJson('/api/storefront/cart');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'product_type',
                        'product_id',
                        'title',
                        'image',
                        'price',
                        'quantity',
                        'line_total',
                        'availability'
                    ]
                ],
                'summary' => [
                    'subtotal',
                    'total_items'
                ]
            ]
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data['items']);

        // Subtotal: (4500 * 1) + (1000 * 3) = 7500
        $this->assertEquals(7500.0, $data['summary']['subtotal']);
        $this->assertEquals(4, $data['summary']['total_items']);

        // Verify diamond detail resolution
        $diamondItem = collect($data['items'])->firstWhere('product_type', 'diamond');
        $this->assertEquals('1.2 CT Round Diamond', $diamondItem['title']); // floatval(1.200) -> 1.2
        $this->assertEquals(asset('/images/cart-dia.png'), $diamondItem['image']);
        $this->assertEquals(4500.0, $diamondItem['price']);
        $this->assertEquals(1, $diamondItem['quantity']);
        $this->assertEquals(4500.0, $diamondItem['line_total']);
        $this->assertEquals('available', $diamondItem['availability']);

        // Verify jewellery detail resolution
        $jewelItem = collect($data['items'])->firstWhere('product_type', 'jewellery');
        $this->assertEquals('Gold Ring', $jewelItem['title']);
        $this->assertEquals(asset('/images/cart-ring.png'), $jewelItem['image']);
        $this->assertEquals(1000.0, $jewelItem['price']);
        $this->assertEquals(3, $jewelItem['quantity']);
        $this->assertEquals(3000.0, $jewelItem['line_total']);
        $this->assertEquals('available', $jewelItem['availability']);
    }

    /**
     * 8. Test Customer can update jewellery quantity.
     */
    public function test_customer_can_update_jewellery_quantity()
    {
        $item = CustomerCartItem::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'jewellery',
            'product_id' => $this->jewellery->id,
            'quantity' => 1
        ]);

        Sanctum::actingAs($this->customerA);

        $response = $this->putJson("/api/storefront/cart/{$item->id}", [
            'quantity' => 5
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Cart item updated.'
        ]);

        $this->assertEquals(5, $item->refresh()->quantity);
    }

    /**
     * 9. Test Customer cannot update diamond quantity.
     */
    public function test_customer_cannot_update_diamond_quantity()
    {
        $item = CustomerCartItem::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id,
            'quantity' => 1
        ]);

        Sanctum::actingAs($this->customerA);

        $response = $this->putJson("/api/storefront/cart/{$item->id}", [
            'quantity' => 2
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Diamond quantity cannot be modified.'
        ]);

        $this->assertEquals(1, $item->refresh()->quantity);
    }

    /**
     * 10. Test Customer can remove own cart item.
     */
    public function test_customer_can_remove_own_cart_item()
    {
        $item = CustomerCartItem::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id,
            'quantity' => 1
        ]);

        Sanctum::actingAs($this->customerA);

        $response = $this->deleteJson("/api/storefront/cart/{$item->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Item removed from cart.'
        ]);

        $this->assertDatabaseMissing('customer_cart_items', ['id' => $item->id]);
    }

    /**
     * 11. Test Customer cannot remove another customer's cart item.
     */
    public function test_customer_cannot_remove_another_customer_cart_item()
    {
        $item = CustomerCartItem::create([
            'customer_id' => $this->customerB->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id,
            'quantity' => 1
        ]);

        Sanctum::actingAs($this->customerA);

        $response = $this->deleteJson("/api/storefront/cart/{$item->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('customer_cart_items', ['id' => $item->id]);
    }

    /**
     * 12. Test Cart count endpoint works.
     */
    public function test_cart_count_endpoint_works()
    {
        Sanctum::actingAs($this->customerA);

        // Initially 0
        $this->getJson('/api/storefront/cart/count')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'count' => 0]);

        // Add 1 diamond
        CustomerCartItem::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id,
            'quantity' => 1
        ]);

        // Add 3 jewellery
        CustomerCartItem::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'jewellery',
            'product_id' => $this->jewellery->id,
            'quantity' => 3
        ]);

        // Total count = 1 + 3 = 4
        $this->getJson('/api/storefront/cart/count')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'count' => 4]);
    }

    /**
     * 13. Test Guest receives 401 Unauthorized.
     */
    public function test_guest_receives_401_unauthorized()
    {
        $this->getJson('/api/storefront/cart')->assertStatus(401);
        $this->postJson('/api/storefront/cart', [])->assertStatus(401);
        $this->putJson('/api/storefront/cart/1', [])->assertStatus(401);
        $this->deleteJson('/api/storefront/cart/1')->assertStatus(401);
        $this->getJson('/api/storefront/cart/count')->assertStatus(401);
    }

    /**
     * 14. Test Admin receives 403 Forbidden.
     */
    public function test_admin_receives_403_forbidden()
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/storefront/cart')->assertStatus(403);
        $this->postJson('/api/storefront/cart', [
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id
        ])->assertStatus(403);
        
        $item = CustomerCartItem::create([
            'customer_id' => $this->customerA->id,
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id,
            'quantity' => 1
        ]);

        $this->putJson("/api/storefront/cart/{$item->id}", ['quantity' => 2])->assertStatus(403);
        $this->deleteJson("/api/storefront/cart/{$item->id}")->assertStatus(403);
        $this->getJson('/api/storefront/cart/count')->assertStatus(403);
    }
}
