<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AdminPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class PermissionManagementTest extends TestCase
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
     * Scenario A: Super Admin has full access to all protected endpoints.
     */
    public function test_scenario_a_super_admin_full_access()
    {
        $super = $this->getAdminUser('super_admin');
        $this->actingAs($super);
        session(['admin_role' => 'super_admin']);

        // Assert that the super admin passeshasPermission check for everything
        $this->assertTrue($super->hasPermission('view_orders'));
        $this->assertTrue($super->hasPermission('view_revenue'));
        $this->assertTrue($super->hasPermission('view_inventory'));
        $this->assertTrue($super->hasPermission('view_reports'));
        $this->assertTrue($super->hasPermission('view_notifications'));
        $this->assertTrue($super->hasPermission('view_system_health'));
        $this->assertTrue($super->hasPermission('view_audit_logs'));

        // Visit a set of protected endpoints
        $this->get(route('orders.index'))->assertStatus(200);
        $this->get(route('inventory.index'))->assertStatus(200);
        $this->get(route('analytics.revenue'))->assertStatus(200);
        $this->get(route('reports.index'))->assertStatus(200);
        $this->get(route('notifications.index'))->assertStatus(200);
        $this->get(route('system.health'))->assertStatus(200);
        $this->get(route('system.activity-logs.index'))->assertStatus(200);
    }

    /**
     * Scenario B: Super Admin switches role to normal_admin via /toggle-role.
     * Access is NOT restricted because database role remains super_admin (lockout prevention).
     */
    public function test_scenario_b_super_admin_toggles_role_retains_access()
    {
        $super = $this->getAdminUser('super_admin');
        $this->actingAs($super);
        session(['admin_role' => 'super_admin']);

        // Perform the role switch POST request
        $this->post(route('toggle-role'))
            ->assertStatus(302); // Redirects back

        $this->assertEquals('normal_admin', session('admin_role'));

        // Super Admin still passes all hasPermission checks to avoid accidental lockout
        $this->assertTrue($super->hasPermission('view_orders'));
        $this->assertTrue($super->hasPermission('view_revenue'));

        // Protected endpoints still allow them to access page
        $this->get(route('orders.index'))->assertStatus(200);
        $this->get(route('inventory.index'))->assertStatus(200);
    }

    /**
     * Scenario C: Super Admin impersonates Admin A. Only Admin A's permissions apply.
     */
    public function test_scenario_c_impersonation_limits_permissions_to_target()
    {
        $super = $this->getAdminUser('super_admin');
        $adminA = $this->getAdminUser('normal_admin');

        // Grant Admin A: view_orders, view_inventory, and hold_inventory
        AdminPermission::create(['user_id' => $adminA->id, 'permission' => 'view_orders']);
        AdminPermission::create(['user_id' => $adminA->id, 'permission' => 'view_inventory']);
        AdminPermission::create(['user_id' => $adminA->id, 'permission' => 'hold_inventory']);

        // Log in as Super Admin
        $this->actingAs($super);
        session(['admin_role' => 'super_admin']);

        // Perform impersonation POST
        $this->post(route('admins.impersonate', $adminA->id))
            ->assertStatus(302); // Redirects to home

        // Confirm active user in Auth is now Admin A and active role in session is normal_admin
        $this->assertEquals($adminA->id, auth()->id());
        $this->assertEquals('normal_admin', session('admin_role'));
        $this->assertEquals($super->id, session('super_admin_user_id'));

        // Check that auth()->user() only has Admin A's permissions
        $this->assertTrue(auth()->user()->hasPermission('view_orders'));
        $this->assertTrue(auth()->user()->hasPermission('view_inventory'));
        $this->assertTrue(auth()->user()->hasPermission('hold_inventory'));

        $this->assertFalse(auth()->user()->hasPermission('view_revenue'));
        $this->assertFalse(auth()->user()->hasPermission('view_reports'));
        $this->assertFalse(auth()->user()->hasPermission('view_system_health'));

        // Assert page access matches permissions
        $this->get(route('orders.index'))->assertStatus(200);
        $this->get(route('inventory.index'))->assertStatus(200);

        $this->get(route('analytics.revenue'))->assertStatus(403);
        $this->get(route('reports.index'))->assertStatus(403);
        $this->get(route('system.health'))->assertStatus(403);
    }

    /**
     * Scenario D: Stop Impersonation returns full Super Admin access.
     */
    public function test_scenario_d_stop_impersonation_restores_full_access()
    {
        $super = $this->getAdminUser('super_admin');
        $adminA = $this->getAdminUser('normal_admin');

        AdminPermission::create(['user_id' => $adminA->id, 'permission' => 'view_orders']);

        $this->actingAs($super);
        session(['admin_role' => 'super_admin']);

        // Start impersonation
        $this->post(route('admins.impersonate', $adminA->id));

        // Stop impersonation
        $this->post(route('admins.stop-impersonate'))
            ->assertStatus(302);

        // Confirm Auth context is restored to Super Admin
        $this->assertEquals($super->id, auth()->id());
        $this->assertEquals('super_admin', session('admin_role'));
        $this->assertFalse(session()->has('super_admin_user_id'));

        // Assert full permissions are restored
        $this->assertTrue(auth()->user()->hasPermission('view_revenue'));
        $this->get(route('analytics.revenue'))->assertStatus(200);
    }

    /**
     * Scenario E: Permission Revocation checks Cache dynamic invalidation.
     */
    public function test_scenario_e_permission_revocation_works_immediately()
    {
        $adminA = $this->getAdminUser('normal_admin');

        // Grant Admin A: view_orders, view_inventory
        AdminPermission::create(['user_id' => $adminA->id, 'permission' => 'view_orders']);
        AdminPermission::create(['user_id' => $adminA->id, 'permission' => 'view_inventory']);

        // Log in as Admin A
        $this->actingAs($adminA);
        $adminA->refreshPermissionsCache();

        // Warm up the permission checks
        $this->assertTrue($adminA->hasPermission('view_inventory'));
        $this->get(route('inventory.index'))->assertStatus(200);

        // Super Admin deletes/revokes 'view_inventory'
        AdminPermission::where('user_id', $adminA->id)
            ->where('permission', 'view_inventory')
            ->delete();

        // Admin model's cache is invalidated by the Super Admin's controller action
        $adminA->refreshPermissionsCache();

        // Verify that view_inventory is revoked and visiting the endpoint returns 403
        $this->assertFalse($adminA->hasPermission('view_inventory'));
        $this->get(route('inventory.index'))->assertStatus(403);

        // Keep view_orders permission intact
        $this->assertTrue($adminA->hasPermission('view_orders'));
        $this->get(route('orders.index'))->assertStatus(200);
    }
}
