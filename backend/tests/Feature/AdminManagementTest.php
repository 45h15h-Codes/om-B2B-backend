<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AdminPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AdminManagementTest extends TestCase
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
     * 1. Verify user profile update does not modify permissions.
     */
    public function test_updating_user_info_does_not_change_permissions()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        $normalAdmin = $this->getAdminUser('normal_admin');

        // Give normal admin some permissions first
        AdminPermission::create(['user_id' => $normalAdmin->id, 'permission' => 'view_orders']);
        AdminPermission::create(['user_id' => $normalAdmin->id, 'permission' => 'view_inventory']);

        // Assert they are in the database
        $this->assertDatabaseHas('admin_permissions', ['user_id' => $normalAdmin->id, 'permission' => 'view_orders']);
        $this->assertDatabaseHas('admin_permissions', ['user_id' => $normalAdmin->id, 'permission' => 'view_inventory']);

        // Update profile fields
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->put(route('admins.update', $normalAdmin->id), [
                'name' => 'Updated Admin Name',
                'email' => 'updated_admin@test.com',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123'
            ]);

        $response->assertRedirect(route('admins.index'));
        $response->assertSessionHas('success', 'Normal admin updated successfully!');

        // Refresh and check profile updated
        $normalAdmin->refresh();
        $this->assertEquals('Updated Admin Name', $normalAdmin->name);
        $this->assertEquals('updated_admin@test.com', $normalAdmin->email);
        $this->assertTrue(Hash::check('newpassword123', $normalAdmin->password));

        // Permissions should remain exactly the same
        $this->assertDatabaseHas('admin_permissions', ['user_id' => $normalAdmin->id, 'permission' => 'view_orders']);
        $this->assertDatabaseHas('admin_permissions', ['user_id' => $normalAdmin->id, 'permission' => 'view_inventory']);
    }

    /**
     * 2. Verify permission update does not modify user details.
     */
    public function test_updating_permissions_does_not_change_user_info()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        $normalAdmin = $this->getAdminUser('normal_admin');

        $originalName = $normalAdmin->name;
        $originalEmail = $normalAdmin->email;
        $originalPassword = $normalAdmin->password;

        // Perform permission update PATCH request
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->patch(route('admins.permissions.update', $normalAdmin->id), [
                'permissions' => ['view_orders', 'view_revenue']
            ]);

        $response->assertRedirect(route('admins.index'));
        $response->assertSessionHas('success', 'Permissions updated successfully!');

        // Refresh and check user profile is unchanged
        $normalAdmin->refresh();
        $this->assertEquals($originalName, $normalAdmin->name);
        $this->assertEquals($originalEmail, $normalAdmin->email);
        $this->assertEquals($originalPassword, $normalAdmin->password);

        // Permissions are updated
        $this->assertDatabaseHas('admin_permissions', ['user_id' => $normalAdmin->id, 'permission' => 'view_orders']);
        $this->assertDatabaseHas('admin_permissions', ['user_id' => $normalAdmin->id, 'permission' => 'view_revenue']);
        $this->assertDatabaseMissing('admin_permissions', ['user_id' => $normalAdmin->id, 'permission' => 'view_inventory']);
    }

    /**
     * 3. Verify Super Admin can update permissions.
     */
    public function test_super_admin_can_update_permissions()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        $normalAdmin = $this->getAdminUser('normal_admin');

        // Super Admin submits PATCH to update normal admin permissions
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->patch(route('admins.permissions.update', $normalAdmin->id), [
                'permissions' => ['view_orders', 'hold_inventory']
            ]);

        $response->assertRedirect(route('admins.index'));
        
        $this->assertDatabaseHas('admin_permissions', ['user_id' => $normalAdmin->id, 'permission' => 'view_orders']);
        $this->assertDatabaseHas('admin_permissions', ['user_id' => $normalAdmin->id, 'permission' => 'hold_inventory']);
    }

    /**
     * 4. Verify Normal Admin access is blocked.
     */
    public function test_normal_admin_access_is_blocked_on_all_admin_management_endpoints()
    {
        $normalAdmin1 = $this->getAdminUser('normal_admin');
        $normalAdmin2 = $this->getAdminUser('normal_admin');

        // Log in as Normal Admin 1
        $this->actingAs($normalAdmin1)
            ->withSession(['admin_role' => 'normal_admin']);

        // Check index list
        $this->get(route('admins.index'))->assertStatus(403);

        // Check edit user endpoint
        $this->put(route('admins.update', $normalAdmin2->id), [
            'name' => 'Attack Profile',
            'email' => 'attack@test.com'
        ])->assertStatus(403);

        // Check permissions update endpoint
        $this->patch(route('admins.permissions.update', $normalAdmin2->id), [
            'permissions' => ['view_orders']
        ])->assertStatus(403);
    }
}
