<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ShopifyStore;
use App\Models\ShopifyProduct;
use App\Models\Diamond;
use App\Services\ShopifyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;

class ShopifyMultiStoreTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminUser()
    {
        return User::factory()->create([
            'email' => 'admin_shopify@omgems.com',
            'name' => 'OM Shopify Admin',
            'role' => 'normal_admin'
        ]);
    }

    /**
     * Test listing connected stores.
     */
    public function test_stores_list_loads()
    {
        $user = $this->getAdminUser();
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Test Store 1',
            'shop_domain' => 'store1.myshopify.com',
            'access_token' => 'shpat_token1',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get(route('shopify.stores'));

        $response->assertStatus(200);
        $response->assertSee('Test Store 1');
        $response->assertSee('store1.myshopify.com');
    }

    /**
     * Test connecting a new store successfully.
     */
    public function test_connect_store_successfully()
    {
        $user = $this->getAdminUser();

        Http::fake([
            'https://new-store.myshopify.com/admin/api/2025-10/shop.json' => Http::response(['shop' => ['id' => 123]], 200)
        ]);

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post(route('shopify.connect-store'), [
                'store_name' => 'New Store',
                'shop_domain' => 'https://new-store.myshopify.com/',
                'access_token' => 'shpat_token123'
            ]);

        $response->assertRedirect(route('shopify.stores'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('shopify_stores', [
            'user_id' => $user->id,
            'shop_domain' => 'new-store.myshopify.com',
            'store_name' => 'New Store',
        ]);

        $store = ShopifyStore::where('shop_domain', 'new-store.myshopify.com')->first();
        $this->assertEquals('shpat_token123', $store->getDecryptedAccessToken());

        $user->refresh();
        $this->assertEquals($store->id, $user->active_shopify_store_id);
    }

    /**
     * Test connection verification failure.
     */
    public function test_connect_store_fails_with_invalid_credentials()
    {
        $user = $this->getAdminUser();

        Http::fake([
            'https://bad-store.myshopify.com/admin/api/2025-10/shop.json' => Http::response(['errors' => 'Unauthorized'], 401)
        ]);

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post(route('shopify.connect-store'), [
                'store_name' => 'Bad Store',
                'shop_domain' => 'bad-store.myshopify.com',
                'access_token' => 'shpat_badtoken'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('shopify_stores', [
            'shop_domain' => 'bad-store.myshopify.com',
        ]);
    }

    /**
     * Test switching active store.
     */
    public function test_switch_active_store()
    {
        $user = $this->getAdminUser();
        $store1 = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Store 1',
            'shop_domain' => 'store1.myshopify.com',
            'access_token' => 'shpat_token1',
        ]);
        $store2 = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Store 2',
            'shop_domain' => 'store2.myshopify.com',
            'access_token' => 'shpat_token2',
        ]);

        $user->update(['active_shopify_store_id' => $store1->id]);

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post(route('shopify.set-active-store', $store2->id));

        $response->assertRedirect();
        $user->refresh();
        $this->assertEquals($store2->id, $user->active_shopify_store_id);
    }

    /**
     * Test store deletion / disconnection.
     */
    public function test_delete_store()
    {
        $user = $this->getAdminUser();
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Store To Delete',
            'shop_domain' => 'delete.myshopify.com',
            'access_token' => 'shpat_token',
        ]);

        $user->update(['active_shopify_store_id' => $store->id]);

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->delete(route('shopify.delete-store', $store->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('shopify_stores', ['id' => $store->id]);
        
        $user->refresh();
        $this->assertNull($user->active_shopify_store_id);
    }
}
