<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ShopifyStore;
use App\Models\ShopifyProduct;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Services\ShopifyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShopifyNormalAdminTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminUser($role = 'normal_admin')
    {
        return User::firstOrCreate(
            ['email' => $role === 'super_admin' ? 'super_shopify@omgems.com' : 'admin_shopify@omgems.com'],
            [
                'name' => $role === 'super_admin' ? 'OM Shopify Super' : 'OM Shopify Normal',
                'password' => bcrypt('password'),
                'role' => $role
            ]
        );
    }

    /**
     * Test normal admin dashboard loading.
     */
    public function test_normal_admin_dashboard_loads()
    {
        $user = $this->getAdminUser('normal_admin');
        
        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/shopify');

        $response->assertStatus(200);
        $response->assertSee('My Shopify Storefront');
        $response->assertSee('Shopify Stores Configuration');
    }

    /**
     * Test Shopify credentials verification and connect action.
     */
    public function test_normal_admin_connection_setup()
    {
        $user = $this->getAdminUser('normal_admin');

        // Mock the Shopify API call for shop.json to return success
        Http::fake([
            'https://test-normal-admin-store.myshopify.com/admin/api/2025-10/shop.json' => Http::response(['shop' => ['id' => 123456789]], 200)
        ]);

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post('/shopify/stores/connect', [
                'shop_domain' => 'https://test-normal-admin-store.myshopify.com/',
                'access_token' => 'shpat_testaccesstoken12345',
                'store_name' => 'Test Normal Admin Store'
            ]);

        $response->assertRedirect(route('shopify.stores'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->active_shopify_store_id);
        
        $store = ShopifyStore::find($user->active_shopify_store_id);
        $this->assertEquals('test-normal-admin-store.myshopify.com', $store->shop_domain);
        $this->assertEquals('shpat_testaccesstoken12345', $store->getDecryptedAccessToken());
    }

    /**
     * Test that Shopify connection setup fails on bad response from Shopify.
     */
    public function test_normal_admin_connection_setup_fails_with_invalid_credentials()
    {
        $user = $this->getAdminUser('normal_admin');

        // Mock the Shopify API call for shop.json to return unauthorized
        Http::fake([
            'https://invalid-store.myshopify.com/admin/api/2025-10/shop.json' => Http::response(['errors' => '[API] Invalid API key or access token'], 401)
        ]);

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post('/shopify/stores/connect', [
                'shop_domain' => 'invalid-store.myshopify.com',
                'access_token' => 'shpat_invalidtoken',
                'store_name' => 'Invalid Store'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Check it was not saved
        $this->assertDatabaseMissing('shopify_stores', [
            'shop_domain' => 'invalid-store.myshopify.com'
        ]);
    }

    /**
     * Test ShopifyService dynamic configuration for User.
     */
    public function test_shopify_service_user_configuration()
    {
        $user = $this->getAdminUser('normal_admin');
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Custom User Store',
            'shop_domain' => 'custom-user-store.myshopify.com',
            'access_token' => 'shpat_custom123',
        ]);
        
        $user->update(['active_shopify_store_id' => $store->id]);

        $service = new ShopifyService();
        $service->forUser($user);

        $this->assertEquals('custom-user-store.myshopify.com', $service->getStore());
        
        // Check fallback when user does not have credentials
        $userWithoutStore = User::factory()->create([
            'active_shopify_store_id' => null
        ]);

        $service->forUser($userWithoutStore);
        $this->assertEquals('', $service->getStore());
    }
}
