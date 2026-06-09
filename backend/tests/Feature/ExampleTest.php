<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Diamond;
use App\Models\User;
use App\Models\Jewelery;
use Illuminate\Support\Facades\Auth;

class ExampleTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

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

    public function test_login_page_loads()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_home_page_loads()
    {
        $user = $this->getAdminUser('normal_admin');
        $response = $this->actingAs($user)->get('/home');
        $response->assertStatus(200);
    }

    public function test_diamonds_index_loads()
    {
        $user = $this->getAdminUser('normal_admin');
        $response = $this->actingAs($user)->get('/diamonds');
        $response->assertStatus(200);
    }

    public function test_diamonds_create_loads()
    {
        $user = $this->getAdminUser('normal_admin');
        $response = $this->actingAs($user)->get('/diamonds/create');
        $response->assertStatus(200);
    }

    public function test_diamonds_edit_loads()
    {
        $user = $this->getAdminUser('normal_admin');
        $diamond = Diamond::first();
        if ($diamond) {
            $diamond->update(['user_id' => $user->id, 'assigned_admin_id' => $user->id]);
            $response = $this->actingAs($user)->get("/diamonds/{$diamond->id}/edit");
            $response->assertStatus(200);
        }
    }

    public function test_categories_redirects_for_normal_admin()
    {
        $user = $this->getAdminUser('normal_admin');
        $response = $this->actingAs($user)->withSession(['admin_role' => 'normal_admin'])->get('/categories');
        $response->assertRedirect(route('home'));
    }

    public function test_categories_loads_for_super_admin()
    {
        $user = $this->getAdminUser('super_admin');
        $response = $this->actingAs($user)->withSession(['admin_role' => 'super_admin'])->get('/categories');
        $response->assertStatus(200);
    }

    public function test_super_admin_can_delete_category_option()
    {
        $user = $this->getAdminUser('super_admin');
        
        $category = \App\Models\Category::firstOrCreate(
            ['type' => 'shape'],
            ['names' => []]
        );
        $names = $category->names ?? [];
        if (!in_array('Cushion Brilliant', $names)) {
            $names[] = 'Cushion Brilliant';
            $category->names = $names;
            $category->save();
        }

        $virtualId = \App\Models\Category::encodeId($category->id, 'Cushion Brilliant');

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'super_admin'])
            ->delete(route('categories.destroy', $virtualId));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $category->refresh();
        $this->assertNotContains('Cushion Brilliant', $category->names);
    }

    public function test_super_admin_can_manage_category_option_images()
    {
        $user = $this->getAdminUser('super_admin');
        
        $category = \App\Models\Category::firstOrCreate(
            ['type' => 'shape'],
            ['names' => []]
        );

        // Delete test option if it exists
        $names = $category->names ?? [];
        $names = array_filter($names, function($item) {
            $name = is_array($item) ? ($item['name'] ?? '') : $item;
            return $name !== 'TestShape';
        });
        $category->names = array_values($names);
        $category->save();

        // 1. Store option with image
        $image = \Illuminate\Http\UploadedFile::fake()->create('icon.png', 50, 'image/png');
        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'super_admin'])
            ->post(route('categories.store'), [
                'type' => 'shape',
                'name' => 'TestShape',
                'image' => $image
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $category->refresh();
        $hasImageOption = false;
        $savedImage = null;
        foreach ($category->names as $item) {
            if (is_array($item) && ($item['name'] ?? '') === 'TestShape') {
                $hasImageOption = true;
                $savedImage = $item['image'];
                break;
            }
        }
        $this->assertTrue($hasImageOption);
        $this->assertNotNull($savedImage);

        // 2. Update option image
        $newImage = \Illuminate\Http\UploadedFile::fake()->create('new_icon.png', 50, 'image/png');
        $virtualId = \App\Models\Category::encodeId($category->id, 'TestShape');

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'super_admin'])
            ->put(route('categories.update', $virtualId), [
                'image' => $newImage
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $category->refresh();
        $hasNewImage = false;
        $updatedImage = null;
        foreach ($category->names as $item) {
            if (is_array($item) && ($item['name'] ?? '') === 'TestShape') {
                $hasNewImage = true;
                $updatedImage = $item['image'];
                break;
            }
        }
        $this->assertTrue($hasNewImage);
        $this->assertNotEquals($savedImage, $updatedImage);

        // Cleanup local files created by fake uploads if any
        if ($savedImage && file_exists(public_path($savedImage))) {
            @unlink(public_path($savedImage));
        }
        if ($updatedImage && file_exists(public_path($updatedImage))) {
            @unlink(public_path($updatedImage));
        }

        // Clean up option from DB
        $category->refresh();
        $names = array_filter($category->names, function($item) {
            $name = is_array($item) ? ($item['name'] ?? '') : $item;
            return $name !== 'TestShape';
        });
        $category->names = array_values($names);
        $category->save();
    }

    public function test_diamonds_create_redirects_for_super_admin()
    {
        $user = $this->getAdminUser('super_admin');
        $response = $this->actingAs($user)->withSession(['admin_role' => 'super_admin'])->get('/diamonds/create');
        $response->assertRedirect(route('diamonds.index'));
    }

    public function test_toggle_role_allows_super_admin()
    {
        $user = $this->getAdminUser('super_admin');
        
        $response = $this->actingAs($user)->withSession(['admin_role' => 'super_admin'])->post('/toggle-role');
        $response->assertSessionHas('admin_role', 'normal_admin');
        
        $response = $this->actingAs($user)->withSession(['admin_role' => 'normal_admin'])->post('/toggle-role');
        $response->assertSessionHas('admin_role', 'super_admin');
    }

    public function test_toggle_role_denies_normal_admin()
    {
        $user = $this->getAdminUser('normal_admin');
        
        $response = $this->actingAs($user)->withSession(['admin_role' => 'normal_admin'])->post('/toggle-role');
        $response->assertStatus(403);
    }

    public function test_bulk_import_uploads_csv_successfully()
    {
        $user = $this->getAdminUser('super_admin');
        
        // Create a fake CSV file
        $csvContent = "stock_no,asking_price,shape,size,color,clarity\n";
        $csvContent .= "TESTIMPORT-01,1500.00,Round,1.05,D,FL\n";
        $csvContent .= "TESTIMPORT-02,2800.00,Oval,1.52,E,IF\n";
        
        $tempFile = tempnam(sys_get_temp_dir(), 'test_csv');
        file_put_contents($tempFile, $csvContent);
        
        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempFile,
            'import_diamonds.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'super_admin'])
            ->post('/diamonds/import', [
                'import_file' => $uploadedFile
            ]);

        $response->assertRedirect(route('diamonds.index'));
        $response->assertSessionHas('success');
        
        // Assert database has imported items
        $this->assertDatabaseHas('diamonds', [
            'stock_no' => 'TESTIMPORT-01',
            'asking_price' => 1500.00,
            'shape' => 'Round',
            'size' => 1.05,
            'color' => 'D',
            'clarity' => 'FL'
        ]);

        $this->assertDatabaseHas('diamonds', [
            'stock_no' => 'TESTIMPORT-02',
            'asking_price' => 2800.00,
            'shape' => 'Oval',
            'size' => 1.52,
            'color' => 'E',
            'clarity' => 'IF'
        ]);

        @unlink($tempFile);
    }

    public function test_chat_page_loads()
    {
        $user = $this->getAdminUser('normal_admin');
        $response = $this->actingAs($user)->get('/chat');
        $response->assertStatus(200);
        $response->assertSee('No Active Chats');
    }

    public function test_jewelery_index_page_loads()
    {
        $user = $this->getAdminUser('normal_admin');
        Jewelery::query()->update(['user_id' => $user->id, 'assigned_admin_id' => $user->id]);
        $response = $this->actingAs($user)->get('/jewelery');
        $response->assertStatus(200);
        $response->assertSee('Diamond Ring 14 KT Yellow Gold Jewellery');
    }

    public function test_jewelery_upload_successfully()
    {
        $user = $this->getAdminUser('normal_admin');
        
        $image = \Illuminate\Http\UploadedFile::fake()->create('test_ring.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)
            ->post('/jewelery', [
                'sku' => 'R9-TESTING-123',
                'name' => 'OM Solitaire Platinum Ring',
                'type' => 'Ring',
                'price' => 899.99,
                'location' => 'Surat, India',
                'image_file' => $image,
                'type_style' => 'Solitaire',
                'category' => 'Fine Jewelry',
                'condition' => 'New',
                'brand' => 'OM Gems',
                'quality' => 'Excellent',
                'metal_type' => 'Platinum',
                'metal_karat' => '950 Plat',
                'total_weight' => 4.5,
                'gemstone_type' => 'Diamond',
                'gemstone_shape' => 'Round',
                'carat_weight' => 1.25,
                'lab' => 'GIA',
                'lab_no' => 'GIA123456',
                'lot_no' => 'OM-LOT-123',
                'is_available' => '1',
                'is_available_for_memo' => '1'
            ]);

        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('success');

        $item = \App\Models\Jewelery::where('sku', 'R9-TESTING-123')->first();
        $this->assertNotNull($item);
        $this->assertEquals('OM Solitaire Platinum Ring', $item->name);
        $this->assertEquals('Ring', $item->type);
        $this->assertEquals(899.99, (float) $item->price);
        $this->assertEquals('Surat, India', $item->location);
        $this->assertEquals('Normal Admin', $item->created_by);
        $this->assertEquals('Pending', $item->status);
        
        $this->assertEquals('Solitaire', $item->type_style);
        $this->assertEquals('Fine Jewelry', $item->category);
        $this->assertEquals('New', $item->condition);
        $this->assertEquals('OM Gems', $item->brand);
        $this->assertEquals('Excellent', $item->quality);
        $this->assertEquals('Platinum', $item->metal_type);
        $this->assertEquals('950 Plat', $item->metal_karat);
        $this->assertEquals(4.5, (float) $item->total_weight);
        $this->assertEquals('Diamond', $item->gemstone_type);
        $this->assertEquals('Round', $item->gemstone_shape);
        $this->assertEquals(1.25, (float) $item->carat_weight);
        $this->assertEquals('GIA', $item->lab);
        $this->assertEquals('GIA123456', $item->lab_no);
        $this->assertEquals('OM-LOT-123', $item->lot_no);
        $this->assertTrue((bool) $item->is_available);
        $this->assertTrue((bool) $item->is_available_for_memo);
    }

    public function test_bulk_import_jeweleries_successfully()
    {
        $user = $this->getAdminUser('normal_admin');
        
        // Create a fake CSV file
        $csvContent = "stock_no,title,type,price,location,metal_type,metal_karat,lot_no,is_available\n";
        $csvContent .= "JEWELBULK-01,Gold Diamond Hoop Earrings,Earings,750.00,London,Gold,18 KT,OM-EAR-777,yes\n";
        $csvContent .= "JEWELBULK-02,Sapphire Tennis Bracelet,Bracelet,1200.00,London,Platinum,950 Plat,OM-BRAC-888,no\n";
        
        $tempFile = tempnam(sys_get_temp_dir(), 'test_jewel_csv');
        file_put_contents($tempFile, $csvContent);
        
        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempFile,
            'import_jewelery.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($user)
            ->post('/jewelery/import', [
                'import_file' => $uploadedFile
            ]);

        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('success');
        
        // Assert database has imported items
        $this->assertDatabaseHas('jeweleries', [
            'sku' => 'JEWELBULK-01',
            'name' => 'Gold Diamond Hoop Earrings',
            'type' => 'Earings',
            'price' => 750.00,
            'location' => 'London',
            'specifications->metal_type' => 'Gold',
            'specifications->metal_karat' => '18 KT',
            'specifications->lot_no' => 'OM-EAR-777',
            'specifications->is_available' => true,
            'created_by' => 'Normal Admin',
            'status' => 'Pending'
        ]);

        $this->assertDatabaseHas('jeweleries', [
            'sku' => 'JEWELBULK-02',
            'name' => 'Sapphire Tennis Bracelet',
            'type' => 'Bracelet',
            'price' => 1200.00,
            'location' => 'London',
            'specifications->metal_type' => 'Platinum',
            'specifications->metal_karat' => '950 Plat',
            'specifications->lot_no' => 'OM-BRAC-888',
            'specifications->is_available' => false,
            'created_by' => 'Normal Admin',
            'status' => 'Pending'
        ]);

        @unlink($tempFile);
    }

    public function test_super_admin_can_view_admins_list()
    {
        $user = $this->getAdminUser('super_admin');
        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'super_admin'])
            ->get('/admins');
        
        $response->assertStatus(200);
        $response->assertSee('Add on User Management');
    }

    public function test_normal_admin_cannot_view_admins_list()
    {
        $user = $this->getAdminUser('normal_admin');
        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/admins');
        
        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_normal_admin()
    {
        User::where('email', 'john_created@omgems.com')->delete();
        $user = $this->getAdminUser('super_admin');
        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'super_admin'])
            ->post('/admins', [
                'name' => 'John Created Admin',
                'email' => 'john_created@omgems.com',
                'password' => 'password123',
                'password_confirmation' => 'password123'
            ]);

        $response->assertRedirect(route('admins.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'John Created Admin',
            'email' => 'john_created@omgems.com',
            'role' => 'normal_admin'
        ]);
    }

    public function test_super_admin_can_impersonate_normal_admin_and_switch_back()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        $normalAdmin = User::firstOrCreate(
            ['email' => 'to_impersonate@omgems.com'],
            [
                'name' => 'Impersonated Admin',
                'password' => bcrypt('password'),
                'role' => 'normal_admin'
            ]
        );

        // Impersonate
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post("/admins/{$normalAdmin->id}/impersonate");

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success');
        $this->assertEquals(Auth::id(), $normalAdmin->id);
        $this->assertEquals(session('super_admin_user_id'), $superAdmin->id);
        $this->assertEquals(session('admin_role'), 'normal_admin');

        // Switch back
        $response = $this->post("/admins/stop-impersonate");
        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success');
        $this->assertEquals(Auth::id(), $superAdmin->id);
        $this->assertFalse(session()->has('super_admin_user_id'));
        $this->assertEquals(session('admin_role'), 'super_admin');
    }

    public function test_super_admin_can_edit_normal_admin_profile()
    {
        User::where('email', 'to_edit@omgems.com')->delete();
        $superAdmin = $this->getAdminUser('super_admin');
        $normalAdmin = User::firstOrCreate(
            ['email' => 'to_edit@omgems.com'],
            [
                'name' => 'Original Name',
                'password' => bcrypt('password'),
                'role' => 'normal_admin'
            ]
        );

        // Edit screen loads
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get("/admins/{$normalAdmin->id}/edit");
        $response->assertStatus(200);

        // Submit edit request
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->put("/admins/{$normalAdmin->id}", [
                'name' => 'Updated Name',
                'email' => 'to_edit@omgems.com',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123'
            ]);

        $response->assertRedirect(route('admins.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $normalAdmin->id,
            'name' => 'Updated Name',
            'email' => 'to_edit@omgems.com'
        ]);
    }

    public function test_normal_admin_cannot_edit_profiles()
    {
        User::where('email', 'other_admin@omgems.com')->delete();
        $normalAdmin = $this->getAdminUser('normal_admin');
        $otherAdmin = User::firstOrCreate(
            ['email' => 'other_admin@omgems.com'],
            ['name' => 'Other Admin', 'password' => bcrypt('password'), 'role' => 'normal_admin']
        );

        $response = $this->actingAs($normalAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get("/admins/{$otherAdmin->id}/edit");
        $response->assertStatus(403);

        $response = $this->actingAs($normalAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->put("/admins/{$otherAdmin->id}", [
                'name' => 'Malicious Hack',
                'email' => 'other_admin@omgems.com'
            ]);
        $response->assertStatus(403);
    }

    public function test_normal_admin_only_sees_their_own_diamonds()
    {
        $admin1 = User::firstOrCreate(
            ['email' => 'admin1@omgems.com'],
            ['name' => 'Admin One', 'password' => bcrypt('password'), 'role' => 'normal_admin']
        );
        $admin2 = User::firstOrCreate(
            ['email' => 'admin2@omgems.com'],
            ['name' => 'Admin Two', 'password' => bcrypt('password'), 'role' => 'normal_admin']
        );

        Diamond::whereIn('stock_no', ['D-11111', 'D-22222'])->delete();

        $diamond1 = Diamond::create([
            'stock_no' => 'D-11111',
            'asking_price' => 1000.00,
            'shape' => 'Round',
            'size' => 1.0,
            'user_id' => $admin1->id,
            'assigned_admin_id' => $admin1->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved'
        ]);

        $diamond2 = Diamond::create([
            'stock_no' => 'D-22222',
            'asking_price' => 2000.00,
            'shape' => 'Oval',
            'size' => 2.0,
            'user_id' => $admin2->id,
            'assigned_admin_id' => $admin2->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved'
        ]);

        $response = $this->actingAs($admin1)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/diamonds?search_active=1');

        $response->assertStatus(200);
        $response->assertSee('D-11111');
        $response->assertDontSee('D-22222');
    }

    public function test_normal_admin_only_sees_their_own_jewelry()
    {
        $admin1 = User::firstOrCreate(
            ['email' => 'admin1@omgems.com'],
            ['name' => 'Admin One', 'password' => bcrypt('password'), 'role' => 'normal_admin']
        );
        $admin2 = User::firstOrCreate(
            ['email' => 'admin2@omgems.com'],
            ['name' => 'Admin Two', 'password' => bcrypt('password'), 'role' => 'normal_admin']
        );

        Jewelery::whereIn('sku', ['J-11111', 'J-22222'])->delete();

        $jewelry1 = Jewelery::create([
            'sku' => 'J-11111',
            'name' => 'Ring One Unique Name',
            'type' => 'Ring',
            'price' => 500.00,
            'location' => 'London',
            'user_id' => $admin1->id,
            'assigned_admin_id' => $admin1->id,
        ]);

        $jewelry2 = Jewelery::create([
            'sku' => 'J-22222',
            'name' => 'Ring Two Unique Name',
            'type' => 'Ring',
            'price' => 600.00,
            'location' => 'Paris',
            'user_id' => $admin2->id,
            'assigned_admin_id' => $admin2->id,
        ]);

        $response = $this->actingAs($admin1)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/jewelery');

        $response->assertStatus(200);
        $response->assertSee('Ring One Unique Name');
        $response->assertDontSee('Ring Two Unique Name');
    }

    public function test_normal_admin_cannot_edit_other_admins_diamonds()
    {
        $admin1 = User::firstOrCreate(
            ['email' => 'admin1@omgems.com'],
            ['name' => 'Admin One', 'password' => bcrypt('password'), 'role' => 'normal_admin']
        );
        $admin2 = User::firstOrCreate(
            ['email' => 'admin2@omgems.com'],
            ['name' => 'Admin Two', 'password' => bcrypt('password'), 'role' => 'normal_admin']
        );

        Diamond::whereIn('stock_no', ['D-11111', 'D-22222'])->delete();

        $diamond2 = Diamond::create([
            'stock_no' => 'D-22222',
            'asking_price' => 2000.00,
            'shape' => 'Oval',
            'size' => 2.0,
            'user_id' => $admin2->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved'
        ]);

        $response = $this->actingAs($admin1)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get("/diamonds/{$diamond2->id}/edit");
        $response->assertStatus(403);

        $response = $this->actingAs($admin1)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get("/diamonds/{$diamond2->id}");
        $response->assertStatus(403);

        $response = $this->actingAs($admin1)
            ->withSession(['admin_role' => 'normal_admin'])
            ->put("/diamonds/{$diamond2->id}", [
                'stock_no' => 'D-22222-hack',
                'asking_price' => 9999.00
            ]);
        $response->assertStatus(403);
    }

    public function test_super_admin_can_see_all_diamonds_and_jewelry()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        $admin1 = User::firstOrCreate(
            ['email' => 'admin1@omgems.com'],
            ['name' => 'Admin One', 'password' => bcrypt('password'), 'role' => 'normal_admin']
        );
        $admin2 = User::firstOrCreate(
            ['email' => 'admin2@omgems.com'],
            ['name' => 'Admin Two', 'password' => bcrypt('password'), 'role' => 'normal_admin']
        );

        Diamond::whereIn('stock_no', ['D-11111', 'D-22222'])->delete();
        Jewelery::whereIn('sku', ['J-11111', 'J-22222'])->delete();

        $diamond1 = Diamond::create([
            'stock_no' => 'D-11111',
            'user_id' => $admin1->id,
        ]);
        $diamond2 = Diamond::create([
            'stock_no' => 'D-22222',
            'user_id' => $admin2->id,
        ]);
        $jewelry1 = Jewelery::create([
            'sku' => 'J-11111',
            'name' => 'Ring One Unique Name',
            'type' => 'Ring',
            'user_id' => $admin1->id,
        ]);
        $jewelry2 = Jewelery::create([
            'sku' => 'J-22222',
            'name' => 'Ring Two Unique Name',
            'type' => 'Ring',
            'user_id' => $admin2->id,
        ]);

        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get('/diamonds?search_active=1');
        $response->assertStatus(200);
        $response->assertSee('D-11111');
        $response->assertSee('D-22222');

        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get('/jewelery');
        $response->assertStatus(200);
        $response->assertSee('Ring One Unique Name');
        $response->assertSee('Ring Two Unique Name');
    }

    public function test_super_admin_impersonating_normal_admin_sees_only_that_admins_data()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        $admin1 = User::firstOrCreate(
            ['email' => 'admin1@omgems.com'],
            ['name' => 'Admin One', 'password' => bcrypt('password'), 'role' => 'normal_admin']
        );
        $admin2 = User::firstOrCreate(
            ['email' => 'admin2@omgems.com'],
            ['name' => 'Admin Two', 'password' => bcrypt('password'), 'role' => 'normal_admin']
        );

        Diamond::whereIn('stock_no', ['D-11111', 'D-22222'])->delete();
        Jewelery::whereIn('sku', ['J-11111', 'J-22222'])->delete();

        $diamond1 = Diamond::create([
            'stock_no' => 'D-11111',
            'user_id' => $admin1->id,
            'assigned_admin_id' => $admin1->id,
        ]);
        $diamond2 = Diamond::create([
            'stock_no' => 'D-22222',
            'user_id' => $admin2->id,
            'assigned_admin_id' => $admin2->id,
        ]);
        $jewelry1 = Jewelery::create([
            'sku' => 'J-11111',
            'name' => 'Ring One Unique Name',
            'type' => 'Ring',
            'user_id' => $admin1->id,
            'assigned_admin_id' => $admin1->id,
        ]);
        $jewelry2 = Jewelery::create([
            'sku' => 'J-22222',
            'name' => 'Ring Two Unique Name',
            'type' => 'Ring',
            'user_id' => $admin2->id,
            'assigned_admin_id' => $admin2->id,
        ]);

        // Start Impersonating admin1
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post("/admins/{$admin1->id}/impersonate");
        
        $response->assertRedirect(route('home'));

        // View diamonds listing as the impersonated user (admin1)
        $response = $this->actingAs($admin1)
            ->withSession([
                'super_admin_user_id' => $superAdmin->id,
                'admin_role' => 'normal_admin'
            ])
            ->get('/diamonds?search_active=1');

        $response->assertStatus(200);
        $response->assertSee('D-11111');
        $response->assertDontSee('D-22222');

        // View jewelry listing as the impersonated user (admin1)
        $response = $this->actingAs($admin1)
            ->withSession([
                'super_admin_user_id' => $superAdmin->id,
                'admin_role' => 'normal_admin'
            ])
            ->get('/jewelery');

        $response->assertStatus(200);
        $response->assertSee('Ring One Unique Name');
        $response->assertDontSee('Ring Two Unique Name');
    }

    public function test_super_admin_sees_approve_reject_delete_actions()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        
        Diamond::whereIn('stock_no', ['D-33333'])->delete();
        
        $diamond = Diamond::create([
            'stock_no' => 'D-33333',
            'status' => 'Pending'
        ]);

        // Get index page as super admin
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get('/diamonds?search_active=1');

        $response->assertStatus(200);
        // Assert we see Approve, Reject and Delete forms targeting the routes
        $response->assertSee(route('diamonds.approve', $diamond->id));
        $response->assertSee(route('diamonds.reject', $diamond->id));
        $response->assertSee(route('diamonds.destroy', $diamond->id));

        // Get show page as super admin
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get("/diamonds/{$diamond->id}");

        $response->assertStatus(200);
        $response->assertSee(route('diamonds.approve', $diamond->id));
        $response->assertSee(route('diamonds.reject', $diamond->id));
        $response->assertSee(route('diamonds.destroy', $diamond->id));
    }

    public function test_normal_admin_does_not_see_super_admin_actions()
    {
        $normalAdmin = $this->getAdminUser('normal_admin');
        
        Diamond::whereIn('stock_no', ['D-33333'])->delete();
        
        $diamond = Diamond::create([
            'stock_no' => 'D-33333',
            'status' => 'Pending',
            'user_id' => $normalAdmin->id,
            'assigned_admin_id' => $normalAdmin->id
        ]);

        // Get index page as normal admin
        $response = $this->actingAs($normalAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/diamonds?search_active=1');

        $response->assertStatus(200);
        // Assert we do NOT see Approve, Reject, or Delete forms
        $response->assertDontSee(route('diamonds.approve', $diamond->id));
        $response->assertDontSee(route('diamonds.reject', $diamond->id));
        $response->assertDontSee('action="' . route('diamonds.destroy', $diamond->id) . '"');

        // Get show page as normal admin
        $response = $this->actingAs($normalAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get("/diamonds/{$diamond->id}");

        $response->assertStatus(200);
        $response->assertDontSee(route('diamonds.approve', $diamond->id));
        $response->assertDontSee(route('diamonds.reject', $diamond->id));
        $response->assertDontSee('action="' . route('diamonds.destroy', $diamond->id) . '"');
    }

    public function test_super_admin_can_update_category_option_group()
    {
        $user = $this->getAdminUser('super_admin');
        
        $category = \App\Models\Category::firstOrCreate(
            ['type' => 'shape'],
            ['names' => []]
        );

        // Add a test option
        $names = $category->names ?? [];
        $names = array_filter($names, function($item) {
            $name = is_array($item) ? ($item['name'] ?? '') : $item;
            return $name !== 'TestGroupShape';
        });
        $names[] = 'TestGroupShape';
        $category->names = array_values($names);
        $category->save();

        $virtualId = \App\Models\Category::encodeId($category->id, 'TestGroupShape');

        // Update the group of the option to 'advance'
        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'super_admin'])
            ->put(route('categories.update', $virtualId), [
                'group' => 'advance'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $category->refresh();
        $groupOption = null;
        foreach ($category->names as $item) {
            if (is_array($item) && ($item['name'] ?? '') === 'TestGroupShape') {
                $groupOption = $item;
                break;
            }
        }
        $this->assertNotNull($groupOption);
        $this->assertEquals('advance', $groupOption['group']);

        // Assert getNamesByGroup returns it
        $advanceShapes = \App\Models\Category::getNamesByGroup('shape', 'advance');
        $this->assertContains('TestGroupShape', $advanceShapes);

        // Clean up
        $names = array_filter($category->names, function($item) {
            $name = is_array($item) ? ($item['name'] ?? '') : $item;
            return $name !== 'TestGroupShape';
        });
        $category->names = array_values($names);
        $category->save();
    }

    public function test_normal_admin_can_create_diamond_with_advance_shape_detail()
    {
        $user = $this->getAdminUser('normal_admin');
        
        Diamond::where('stock_no', 'TESTADV-111')->delete();

        $response = $this->actingAs($user)
            ->post(route('diamonds.store'), [
                'stock_no' => 'TESTADV-111',
                'shape' => 'Cushion Modified',
                'size' => 1.25,
                'color' => 'D',
                'clarity' => 'VVS1',
                'advance_shape_enabled' => '1',
                'advance_shape_detail' => 'Modified'
            ]);

        $response->assertRedirect(route('diamonds.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('diamonds', [
            'stock_no' => 'TESTADV-111',
            'shape' => 'Cushion Modified',
            'specifications->advance_shape_enabled' => true,
            'specifications->advance_shape_detail' => 'Modified'
        ]);

        // Clean up
        Diamond::where('stock_no', 'TESTADV-111')->delete();
    }

    public function test_super_admin_cannot_upload_jewelry()
    {
        $user = $this->getAdminUser('super_admin');
        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'super_admin'])
            ->post('/jewelery', [
                'sku' => 'J-SUPER-UPLOAD',
                'name' => 'OM Solitaire Platinum Ring',
                'type' => 'Ring',
                'price' => 899.99,
                'location' => 'Surat, India'
            ]);
        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('error');
    }

    public function test_normal_admin_upload_jewelry_is_pending_and_super_admin_can_approve_or_reject()
    {
        $normalAdmin = $this->getAdminUser('normal_admin');
        $superAdmin = $this->getAdminUser('super_admin');

        Jewelery::where('sku', 'J-PEND-TEST')->delete();

        $image = \Illuminate\Http\UploadedFile::fake()->create('test_ring.jpg', 100, 'image/jpeg');

        // 1. Upload by normal admin
        $response = $this->actingAs($normalAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post('/jewelery', [
                'sku' => 'J-PEND-TEST',
                'name' => 'Normal Admin Pending Ring',
                'type' => 'Ring',
                'price' => 500.00,
                'location' => 'London',
                'image_file' => $image
            ]);
        
        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('success');

        // Assert is Pending
        $this->assertDatabaseHas('jeweleries', [
            'sku' => 'J-PEND-TEST',
            'status' => 'Pending'
        ]);

        $item = Jewelery::where('sku', 'J-PEND-TEST')->first();

        // 2. Approve by super admin
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post("/jewelery/{$item->id}/approve");

        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('jeweleries', [
            'sku' => 'J-PEND-TEST',
            'status' => 'Approved'
        ]);

        // 3. Reject by super admin
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post("/jewelery/{$item->id}/reject");

        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('jeweleries', [
            'sku' => 'J-PEND-TEST',
            'status' => 'Rejected'
        ]);

        // Cleanup
        $item->delete();
    }

    public function test_admin_roles_jewelry_edit_update_destroy_authorization()
    {
        $normalAdmin = $this->getAdminUser('normal_admin');
        $otherAdmin = \App\Models\User::factory()->create(['role' => 'normal_admin', 'email' => 'other_admin_' . uniqid() . '@example.com']);
        $superAdmin = $this->getAdminUser('super_admin');

        $item = Jewelery::create([
            'sku' => 'J-AUTH-TEST',
            'name' => 'Auth Test Ring',
            'type' => 'Ring',
            'price' => 500.00,
            'location' => 'London',
            'user_id' => $normalAdmin->id,
            'assigned_admin_id' => $normalAdmin->id,
            'status' => 'Pending'
        ]);

        // 1. Owner can view edit page
        $response = $this->actingAs($normalAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get("/jewelery/{$item->id}/edit");
        $response->assertStatus(200);

        // 2. Non-owner normal admin cannot view edit page
        $response = $this->actingAs($otherAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get("/jewelery/{$item->id}/edit");
        $response->assertStatus(403);

        // 3. Super admin can view edit page
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get("/jewelery/{$item->id}/edit");
        $response->assertStatus(200);

        // 4. Owner can update
        $response = $this->actingAs($normalAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->put("/jewelery/{$item->id}", [
                'sku' => 'J-AUTH-TEST-2',
                'name' => 'Auth Test Ring Updated',
                'type' => 'Ring',
                'price' => 600.00,
                'location' => 'London'
            ]);
        $response->assertRedirect(route('jewelery.index'));
        $this->assertDatabaseHas('jeweleries', [
            'id' => $item->id,
            'sku' => 'J-AUTH-TEST-2',
            'name' => 'Auth Test Ring Updated'
        ]);

        // 5. Non-owner cannot update
        $response = $this->actingAs($otherAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->put("/jewelery/{$item->id}", [
                'sku' => 'J-AUTH-TEST-3',
                'name' => 'Auth Test Ring Hacked',
                'type' => 'Ring',
                'price' => 700.00,
                'location' => 'London'
            ]);
        $response->assertStatus(403);

        // 6. Normal admin cannot delete
        $response = $this->actingAs($normalAdmin)
            ->withSession(['admin_role' => 'normal_admin'])
            ->delete("/jewelery/{$item->id}");
        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('jeweleries', ['id' => $item->id]);

        // 7. Super admin can delete
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->delete("/jewelery/{$item->id}");
        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('jeweleries', ['id' => $item->id]);

        // Cleanup
        $otherAdmin->delete();
    }

    public function test_categories_index_splits_diamond_and_jewelry()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get('/categories');

        $response->assertStatus(200);
        $response->assertSee('Diamond Dropdowns');
        $response->assertSee('Jewelry Dropdowns');
    }
}
