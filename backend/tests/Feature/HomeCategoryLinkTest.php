<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeCategoryLinkTest extends TestCase
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
     * Test that category links exist in the HTML response and redirect correctly.
     */
    public function test_category_links_rendered_and_redirect_correctly()
    {
        $user = $this->getAdminUser('normal_admin');

        // Access the homepage
        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/home');

        $response->assertStatus(200);

        // Verify HTML contains the links with the correct query parameters mapped
        $response->assertSee('jewelery?type=Ring');
        $response->assertSee('jewelery?type=Bracelet');
        $response->assertSee('jewelery?type=Earings');
        $response->assertSee('jewelery?type=Necklace');
        $response->assertSee('jewelery?type=Watch');

        // Test that hitting one of the category links resolves to the jewelry index
        $redirectResponse = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/jewelery?type=Ring');

        $redirectResponse->assertStatus(200);
    }
}
