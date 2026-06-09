<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyStore;
use App\Models\DiamondStoreAssignment;
use App\Models\InventoryHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class EnterpriseSystemFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminUser($role = 'normal_admin')
    {
        return User::create([
            'name' => 'Test ' . ucfirst($role),
            'email' => $role . '_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role' => $role
        ]);
    }

    /**
     * Test DiamondPolicy authorization logic.
     */
    public function test_diamond_policy_authorization()
    {
        $super = $this->getAdminUser('super_admin');
        $adminA = $this->getAdminUser('normal_admin');
        $adminB = $this->getAdminUser('normal_admin');

        // Store for Admin A
        $storeA = ShopifyStore::create([
            'user_id' => $adminA->id,
            'store_name' => 'Store A',
            'shop_domain' => 'store-a.myshopify.com',
            'access_token' => 'token-a',
        ]);

        // Diamond owned by Admin A
        $diamondA = Diamond::create([
            'stock_no' => 'DIA-A',
            'asking_price' => 1000,
            'shape' => 'Round',
            'size' => 0.5,
            'user_id' => $adminA->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        // Diamond owned by Admin B but assigned to Store A
        $diamondB = Diamond::create([
            'stock_no' => 'DIA-B',
            'asking_price' => 2000,
            'shape' => 'Oval',
            'size' => 1.0,
            'user_id' => $adminB->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        DiamondStoreAssignment::create([
            'diamond_id' => $diamondB->id,
            'shopify_store_id' => $storeA->id,
            'sync_status' => 'synced',
            'assigned_by' => $adminB->id,
        ]);

        // 1. Acting as Super Admin
        $this->actingAs($super);
        session(['admin_role' => 'super_admin']);
        $this->assertTrue($super->can('view', $diamondA));
        $this->assertTrue($super->can('update', $diamondA));
        $this->assertTrue($super->can('delete', $diamondA));
        $this->assertTrue($super->can('publish', $diamondA));

        // 2. Acting as Normal Admin A (owner of diamondA, mapped to storeA for diamondB)
        $this->actingAs($adminA);
        session(['admin_role' => 'normal_admin']);

        // Can access owned diamondA
        $this->assertTrue($adminA->can('view', $diamondA));
        $this->assertTrue($adminA->can('update', $diamondA));
        $this->assertFalse($adminA->can('delete', $diamondA));
        $this->assertTrue($adminA->can('publish', $diamondA));

        // Can view assigned diamondB
        $this->assertTrue($adminA->can('view', $diamondB));
        // Cannot edit, delete, or publish diamondB since not owned by Admin A
        $this->assertFalse($adminA->can('update', $diamondB));
        $this->assertFalse($adminA->can('delete', $diamondB));
        $this->assertFalse($adminA->can('publish', $diamondB));

        // Cannot delete if status is on_hold or sold
        $diamondA->update(['inventory_status' => 'on_hold']);
        $this->assertFalse($adminA->can('delete', $diamondA));

        // 3. Acting as Normal Admin B
        $this->actingAs($adminB);
        session(['admin_role' => 'normal_admin']);

        // Cannot view/edit/delete/publish Admin A's diamond
        $this->assertFalse($adminB->can('view', $diamondA));
        $this->assertFalse($adminB->can('update', $diamondA));
        $this->assertFalse($adminB->can('delete', $diamondA));
        $this->assertFalse($adminB->can('publish', $diamondA));
    }

    /**
     * Test JeweleryPolicy authorization logic.
     */
    public function test_jewelry_policy_authorization()
    {
        $super = $this->getAdminUser('super_admin');
        $adminA = $this->getAdminUser('normal_admin');
        $adminB = $this->getAdminUser('normal_admin');

        $jewelryA = Jewelery::create([
            'sku' => 'JW-A',
            'price' => 1500,
            'type' => 'Ring',
            'user_id' => $adminA->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        // 1. Acting as Super Admin
        $this->actingAs($super);
        session(['admin_role' => 'super_admin']);
        $this->assertTrue($super->can('view', $jewelryA));
        $this->assertTrue($super->can('update', $jewelryA));
        $this->assertTrue($super->can('delete', $jewelryA));
        $this->assertTrue($super->can('publish', $jewelryA));

        // 2. Acting as Normal Admin A
        $this->actingAs($adminA);
        session(['admin_role' => 'normal_admin']);
        $this->assertTrue($adminA->can('view', $jewelryA));
        $this->assertTrue($adminA->can('update', $jewelryA));
        $this->assertFalse($adminA->can('delete', $jewelryA));
        $this->assertTrue($adminA->can('publish', $jewelryA));

        // Cannot delete if sold or hold
        $jewelryA->update(['inventory_status' => 'sold']);
        $this->assertFalse($adminA->can('delete', $jewelryA));

        // 3. Acting as Normal Admin B
        $this->actingAs($adminB);
        session(['admin_role' => 'normal_admin']);
        $this->assertFalse($adminB->can('view', $jewelryA));
        $this->assertFalse($adminB->can('update', $jewelryA));
        $this->assertFalse($adminB->can('delete', $jewelryA));
        $this->assertFalse($adminB->can('publish', $jewelryA));
    }

    /**
     * Test ExpireHoldReservations Artisan command sweeps.
     */
    public function test_expire_hold_reservations_command()
    {
        $admin = $this->getAdminUser('normal_admin');

        // Create active Shopify Store so audit logs don't fail foreign key constraint checks
        ShopifyStore::create([
            'id' => 1,
            'user_id' => $admin->id,
            'store_name' => 'Default Store',
            'shop_domain' => 'default.myshopify.com',
            'access_token' => 'token',
            'is_active' => true,
        ]);

        // Diamond A: on_hold > 30 minutes (expired)
        $diaExpired = Diamond::create([
            'stock_no' => 'DIA-EXP',
            'asking_price' => 1200,
            'shape' => 'Round',
            'size' => 0.8,
            'user_id' => $admin->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
            'hold_by' => $admin->id,
            'hold_reason' => 'Customer reserved',
            'hold_at' => now()->subMinutes(31),
        ]);

        // Diamond B: on_hold < 30 minutes (active)
        $diaActive = Diamond::create([
            'stock_no' => 'DIA-ACT',
            'asking_price' => 1300,
            'shape' => 'Round',
            'size' => 0.8,
            'user_id' => $admin->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
            'hold_by' => $admin->id,
            'hold_reason' => 'Customer reserved active',
            'hold_at' => now()->subMinutes(15),
        ]);

        // Jewelry A: on_hold > 30 minutes (expired)
        $jwExpired = Jewelery::create([
            'sku' => 'JW-EXP',
            'price' => 2000,
            'type' => 'Ring',
            'user_id' => $admin->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
            'hold_by' => $admin->id,
            'hold_reason' => 'Hold expired',
            'hold_at' => now()->subMinutes(40),
        ]);

        // Run command
        $exitCode = Artisan::call('sys:expire-reservations');
        $this->assertEquals(0, $exitCode);

        // Assert expired items are released
        $this->assertEquals('available', $diaExpired->fresh()->inventory_status);
        $this->assertNull($diaExpired->fresh()->hold_by);
        $this->assertNull($diaExpired->fresh()->hold_reason);

        $this->assertEquals('available', $jwExpired->fresh()->inventory_status);
        $this->assertNull($jwExpired->fresh()->hold_by);
        $this->assertNull($jwExpired->fresh()->hold_reason);

        // Assert active items are still on hold
        $this->assertEquals('on_hold', $diaActive->fresh()->inventory_status);
        $this->assertEquals($admin->id, $diaActive->fresh()->hold_by);
    }

    /**
     * Test backup permission guards and in-memory sqlite block.
     */
    public function test_backup_utilities_permissions_and_in_memory()
    {
        $super = $this->getAdminUser('super_admin');
        $admin = $this->getAdminUser('normal_admin');

        // 1. Normal admin should be blocked
        $this->actingAs($admin);
        session(['admin_role' => 'normal_admin']);

        $this->get(route('system.backups.index'))->assertStatus(403);
        $this->post(route('system.backups.create'))->assertStatus(403);

        // 2. Super admin on in-memory SQLite should fail with 400
        $this->actingAs($super);
        session(['admin_role' => 'super_admin']);

        $this->get(route('system.backups.index'))->assertStatus(200);
        
        $response = $this->post(route('system.backups.create'));
        $response->assertStatus(400);
        $response->assertJsonFragment([
            'status' => 'error',
            'message' => 'Cannot back up in-memory database.'
        ]);
    }

    /**
     * Test backup operations using a physical file-based SQLite database.
     */
    public function test_backup_creation_and_restoration_with_file_database()
    {
        // Create a temporary sqlite file
        $tempDbFile = tempnam(sys_get_temp_dir(), 'sqlite_test_');
        @unlink($tempDbFile);
        touch($tempDbFile);

        // Set config database to this physical file database connection
        config(['database.connections.sqlite_test' => [
            'driver' => 'sqlite',
            'database' => $tempDbFile,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        config(['database.default' => 'sqlite_test']);

        try {
            // Run migrations on the temp db
            Artisan::call('migrate', ['--database' => 'sqlite_test']);

            $super = $this->getAdminUser('super_admin');
            $this->actingAs($super);
            session(['admin_role' => 'super_admin']);

            // Create backup dir if not exists
            $backupDir = storage_path('app/backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0777, true);
            }

            // Clean up any existing backups
            array_map('unlink', glob($backupDir . '/*.zip'));

            // Create database backup
            $response = $this->post(route('system.backups.create'));
            $response->assertStatus(200);
            $response->assertJsonFragment(['status' => 'success']);

            // Verify backup ZIP is created
            $zipFiles = glob($backupDir . '/*.zip');
            $this->assertCount(1, $zipFiles);
            $filename = basename($zipFiles[0]);

            // Test backup index returns 200 and lists the backup
            $this->get(route('system.backups.index'))
                ->assertStatus(200)
                ->assertSee($filename);

            // Test backup download
            $this->get(route('system.backups.download', $filename))
                ->assertStatus(200);

            // Test restore
            $responseRestore = $this->post(route('system.backups.restore', $filename));
            $responseRestore->assertStatus(200);
            $responseRestore->assertJsonFragment(['status' => 'success']);

            // Test delete
            $responseDelete = $this->delete(route('system.backups.delete', $filename));
            $responseDelete->assertStatus(200);
            $responseDelete->assertJsonFragment(['status' => 'success']);

            // Verify ZIP is deleted
            $this->assertCount(0, glob($backupDir . '/*.zip'));

        } finally {
            // Restore default config and clean up temp db file
            config(['database.default' => 'sqlite']);
            @unlink($tempDbFile);
        }
    }
}
