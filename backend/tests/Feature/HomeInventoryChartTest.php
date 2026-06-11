<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Diamond;
use App\Models\Jewelery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeInventoryChartTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminUser($role = 'normal_admin')
    {
        return User::firstOrCreate(
            ['email' => $role === 'super_admin' ? 'super_home@omgems.com' : 'admin_home@omgems.com'],
            [
                'name' => $role === 'super_admin' ? 'OM Super Home' : 'OM Admin Home',
                'password' => bcrypt('password'),
                'role' => $role
            ]
        );
    }

    /**
     * Test that Super Admin sees all inventory counts in home stats.
     */
    public function test_super_admin_sees_all_inventory_chart_stats()
    {
        $super = $this->getAdminUser('super_admin');
        $admin1 = $this->getAdminUser('normal_admin');
        $admin2 = User::create([
            'name' => 'Admin 2',
            'email' => 'admin2@omgems.com',
            'password' => bcrypt('password'),
            'role' => 'normal_admin'
        ]);

        // 1. Available items
        Diamond::create([
            'stock_no' => 'D-AV-1',
            'asking_price' => 1000.00,
            'shape' => 'Round',
            'size' => 1.0,
            'user_id' => $admin1->id,
            'assigned_admin_id' => $admin1->id,
            'created_by' => 'Admin 1',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);
        Jewelery::create([
            'sku' => 'J-AV-1',
            'name' => 'Ring 1',
            'type' => 'Ring',
            'price' => 500.00,
            'user_id' => $admin2->id,
            'assigned_admin_id' => $admin2->id,
            'created_by' => 'Admin 2',
            'status' => 'Approved',
            'inventory_status' => 'available', // SQLite does not allow null as column has NOT NULL constraint
        ]);

        // 2. On Hold items
        Diamond::create([
            'stock_no' => 'D-HD-1',
            'asking_price' => 2000.00,
            'shape' => 'Oval',
            'size' => 1.5,
            'user_id' => $admin1->id,
            'assigned_admin_id' => $admin1->id,
            'created_by' => 'Admin 1',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
        ]);
        Jewelery::create([
            'sku' => 'J-HD-1',
            'name' => 'Bracelet 1',
            'type' => 'Bracelet',
            'price' => 1200.00,
            'user_id' => $admin2->id,
            'assigned_admin_id' => $admin2->id,
            'created_by' => 'Admin 2',
            'status' => 'Approved',
            'inventory_status' => 'hold',
        ]);

        // 3. Sold items
        Diamond::create([
            'stock_no' => 'D-SD-1',
            'asking_price' => 3000.00,
            'shape' => 'Emerald',
            'size' => 2.0,
            'user_id' => $admin1->id,
            'assigned_admin_id' => $admin1->id,
            'created_by' => 'Admin 1',
            'status' => 'Approved',
            'inventory_status' => 'sold',
        ]);

        // Access /home as Super Admin
        $response = $this->actingAs($super)
            ->withSession(['admin_role' => 'super_admin'])
            ->get('/home');

        $response->assertStatus(200);

        // Verify all items are summed up globally
        $stats = $response->viewData('stats');
        $this->assertEquals(2, $stats['available_count']); // D-AV-1 (available) + J-AV-1 (null status)
        $this->assertEquals(2, $stats['on_hold_count']);    // D-HD-1 (on_hold) + J-HD-1 (hold)
        $this->assertEquals(1, $stats['sold_count']);       // D-SD-1 (sold)
    }

    /**
     * Test that Normal Admin only sees their own assigned inventory counts in home stats.
     */
    public function test_normal_admin_only_sees_assigned_inventory_chart_stats()
    {
        $admin1 = $this->getAdminUser('normal_admin');
        $admin2 = User::create([
            'name' => 'Admin 2',
            'email' => 'admin2@omgems.com',
            'password' => bcrypt('password'),
            'role' => 'normal_admin'
        ]);

        // Admin 1 items: 1 available, 1 on_hold, 1 sold
        Diamond::create([
            'stock_no' => 'D-AV-A1',
            'asking_price' => 1000.00,
            'shape' => 'Round',
            'size' => 1.0,
            'user_id' => $admin1->id,
            'assigned_admin_id' => $admin1->id,
            'created_by' => 'Admin 1',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);
        Diamond::create([
            'stock_no' => 'D-HD-A1',
            'asking_price' => 2000.00,
            'shape' => 'Oval',
            'size' => 1.5,
            'user_id' => $admin1->id,
            'assigned_admin_id' => $admin1->id,
            'created_by' => 'Admin 1',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
        ]);
        Diamond::create([
            'stock_no' => 'D-SD-A1',
            'asking_price' => 3000.00,
            'shape' => 'Emerald',
            'size' => 2.0,
            'user_id' => $admin1->id,
            'assigned_admin_id' => $admin1->id,
            'created_by' => 'Admin 1',
            'status' => 'Approved',
            'inventory_status' => 'sold',
        ]);

        // Admin 2 items (should be excluded for Admin 1): 2 available, 1 on_hold
        Jewelery::create([
            'sku' => 'J-AV-A2',
            'name' => 'Ring A2',
            'type' => 'Ring',
            'price' => 500.00,
            'user_id' => $admin2->id,
            'assigned_admin_id' => $admin2->id,
            'created_by' => 'Admin 2',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);
        Jewelery::create([
            'sku' => 'J-HD-A2',
            'name' => 'Bracelet A2',
            'type' => 'Bracelet',
            'price' => 1200.00,
            'user_id' => $admin2->id,
            'assigned_admin_id' => $admin2->id,
            'created_by' => 'Admin 2',
            'status' => 'Approved',
            'inventory_status' => 'hold',
        ]);

        // Access /home as Admin 1
        $response = $this->actingAs($admin1)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/home');

        $response->assertStatus(200);

        // Verify only Admin 1's items are counted
        $stats = $response->viewData('stats');
        $this->assertEquals(1, $stats['available_count']);
        $this->assertEquals(1, $stats['on_hold_count']);
        $this->assertEquals(1, $stats['sold_count']);
    }
}
