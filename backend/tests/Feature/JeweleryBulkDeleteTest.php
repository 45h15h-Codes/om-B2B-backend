<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Jewelery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JeweleryBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function getAdminUser($role = 'normal_admin')
    {
        return User::firstOrCreate(
            ['email' => $role === 'super_admin' ? 'super@omgems.com' : 'admin@omgems.com'],
            [
                'name' => $role === 'super_admin' ? 'OM Super Admin' : 'OM Normal Admin',
                'password' => bcrypt('password'),
                'role' => $role
            ]
        );
    }

    /**
     * Test validation error when bulk deleting without IDs.
     */
    public function test_validation_errors_when_no_ids_passed()
    {
        $superAdmin = $this->getAdminUser('super_admin');

        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post(route('jewelery.bulk-delete'), []);

        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('error', 'No jewelry items selected for deletion.');
    }

    /**
     * Test that Super Admin can bulk delete any jewelry items.
     */
    public function test_super_admin_can_bulk_delete_jewelery()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        $normalAdmin = $this->getAdminUser('normal_admin');

        // Create some jewelry items
        $jewelry1 = Jewelery::create([
            'sku' => 'JW-TEST-1',
            'name' => 'Gold Ring',
            'type' => 'Ring',
            'price' => 500.00,
            'location' => 'India',
            'user_id' => $normalAdmin->id,
            'assigned_admin_id' => $normalAdmin->id,
            'created_by' => 'Normal Admin'
        ]);

        $jewelry2 = Jewelery::create([
            'sku' => 'JW-TEST-2',
            'name' => 'Diamond Earrings',
            'type' => 'Earings',
            'price' => 1200.00,
            'location' => 'London',
            'user_id' => $normalAdmin->id,
            'assigned_admin_id' => $normalAdmin->id,
            'created_by' => 'Normal Admin'
        ]);

        $this->assertDatabaseHas('jeweleries', ['id' => $jewelry1->id]);
        $this->assertDatabaseHas('jeweleries', ['id' => $jewelry2->id]);

        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post(route('jewelery.bulk-delete'), [
                'ids' => [$jewelry1->id, $jewelry2->id]
            ]);

        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('success', 'Successfully deleted 2 jewelry items.');

        $this->assertDatabaseMissing('jeweleries', ['id' => $jewelry1->id]);
        $this->assertDatabaseMissing('jeweleries', ['id' => $jewelry2->id]);
    }

    /**
     * Test that Normal Admin cannot delete jewelry items they are not authorized to delete.
     */
    public function test_normal_admin_cannot_bulk_delete_unauthorized_jewelery()
    {
        $normalAdmin1 = $this->getAdminUser('normal_admin');
        $normalAdmin2 = User::create([
            'name' => 'Other Normal Admin',
            'email' => 'other_admin@omgems.com',
            'password' => bcrypt('password'),
            'role' => 'normal_admin'
        ]);

        // Create jewelry belonging to Admin 2
        $jewelry1 = Jewelery::create([
            'sku' => 'JW-OWNED-2',
            'name' => 'Admin 2 Pendant',
            'type' => 'Pendent',
            'price' => 750.00,
            'location' => 'USA',
            'user_id' => $normalAdmin2->id,
            'assigned_admin_id' => $normalAdmin2->id,
            'created_by' => 'Normal Admin'
        ]);

        $this->assertDatabaseHas('jeweleries', ['id' => $jewelry1->id]);

        // Admin 1 attempts to bulk delete Admin 2's jewelry
        $response = $this->actingAs($normalAdmin1)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post(route('jewelery.bulk-delete'), [
                'ids' => [$jewelry1->id]
            ]);

        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('error', 'Unauthorized: Only Super Admin can delete jewelry.');

        // Record should still exist
        $this->assertDatabaseHas('jeweleries', ['id' => $jewelry1->id]);
    }

    /**
     * Test that Normal Admin cannot delete even their own jewelry items.
     */
    public function test_normal_admin_cannot_delete_own_jewelery()
    {
        $normalAdmin = $this->getAdminUser('normal_admin');

        $jewelry = Jewelery::create([
            'sku' => 'JW-OWNED-1',
            'name' => 'Admin 1 Ring',
            'type' => 'Ring',
            'price' => 300.00,
            'location' => 'India',
            'user_id' => $normalAdmin->id,
            'assigned_admin_id' => $normalAdmin->id,
            'created_by' => 'Normal Admin'
        ]);

        $this->assertDatabaseHas('jeweleries', ['id' => $jewelry->id]);

        $response = $this->actingAs($normalAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post(route('jewelery.bulk-delete'), [
                'ids' => [$jewelry->id]
            ]);

        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('error', 'Unauthorized: Only Super Admin can delete jewelry.');

        $this->assertDatabaseHas('jeweleries', ['id' => $jewelry->id]);
    }

    /**
     * Test that direct route access by Normal Admin is blocked.
     */
    public function test_direct_route_access_by_normal_admin_is_blocked()
    {
        $normalAdmin = $this->getAdminUser('normal_admin');

        $response = $this->actingAs($normalAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post(route('jewelery.bulk-delete'), [
                'ids' => [9999] // Arbitrary ID
            ]);

        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('error', 'Unauthorized: Only Super Admin can delete jewelry.');
    }

    /**
     * Test that the Bulk Delete button, select checkboxes, and confirmation modal are hidden for Normal Admin users.
     */
    public function test_bulk_delete_button_and_checkboxes_are_hidden_for_normal_admin()
    {
        $normalAdmin = $this->getAdminUser('normal_admin');
        
        // Create a jewelry item so the items count > 0 is satisfied
        Jewelery::create([
            'sku' => 'JW-VIEW-TEST',
            'name' => 'Test Jewelry',
            'type' => 'Ring',
            'price' => 200.00,
            'location' => 'India',
            'user_id' => $normalAdmin->id,
            'assigned_admin_id' => $normalAdmin->id,
            'created_by' => 'Normal Admin'
        ]);

        // Access as Normal Admin
        $responseNormal = $this->actingAs($normalAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get(route('jewelery.index'));

        $responseNormal->assertStatus(200);
        $responseNormal->assertDontSee('id="bulk-delete-btn"', false);
        $responseNormal->assertDontSee('id="select-all-checkbox"', false);
        $responseNormal->assertDontSee('class="jewelry-checkbox"', false);
        $responseNormal->assertDontSee('id="confirmModalOverlay"', false);

        // Access as Super Admin to verify they are visible
        $superAdmin = $this->getAdminUser('super_admin');
        $responseSuper = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get(route('jewelery.index'));

        $responseSuper->assertStatus(200);
        $responseSuper->assertSee('id="bulk-delete-btn"', false);
        $responseSuper->assertSee('id="select-all-checkbox"', false);
        $responseSuper->assertSee('class="jewelry-checkbox"', false);
        $responseSuper->assertSee('id="confirmModalOverlay"', false);
    }
}
