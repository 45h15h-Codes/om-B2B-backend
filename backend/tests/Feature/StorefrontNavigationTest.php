<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the storefront navigation endpoint returns the correct structure.
     */
    public function test_navigation_endpoint_returns_nested_json_structure()
    {
        // 1. Arrange: Create or update categories to be retrieved dynamically
        $shapeCategory = Category::updateOrCreate(
            ['type' => 'shape'],
            [
                'names' => [
                    'Round',
                    'Oval',
                    [
                        'name' => 'Pear',
                        'image' => '/images/categories/pear.png',
                        'group' => 'fancy'
                    ]
                ]
            ]
        );

        $jewelryCategory = Category::updateOrCreate(
            ['type' => 'jewelery_type'],
            ['names' => ['Ring', 'Necklace', 'Bracelet']]
        );

        // 2. Act: Request the navigation API endpoint
        $response = $this->getJson('/api/storefront/navigation');

        // 3. Assert: Verify HTTP status and root format
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'url',
                    'type',
                    'children'
                ]
            ]
        ]);

        $data = $response->json('data');

        // 4. Assert count and order of root menus
        $this->assertCount(6, $data);
        $this->assertEquals('Home', $data[0]['title']);
        $this->assertEquals('Diamonds', $data[1]['title']);
        $this->assertEquals('Jewellery', $data[2]['title']);
        $this->assertEquals('Collections', $data[3]['title']);
        $this->assertEquals('About', $data[4]['title']);
        $this->assertEquals('Contact', $data[5]['title']);

        // 5. Assert Diamonds dynamic dropdown content and alphabetical sorting
        $diamondChildren = $data[1]['children'];
        $this->assertCount(3, $diamondChildren);

        // Sorting: Oval, Pear, Round
        $this->assertEquals('Oval', $diamondChildren[0]['title']);
        $this->assertEquals('/diamonds?shape=Oval', $diamondChildren[0]['url']);
        
        $this->assertEquals('Pear', $diamondChildren[1]['title']);
        $this->assertEquals('/diamonds?shape=Pear', $diamondChildren[1]['url']);
        $this->assertEquals('/images/categories/pear.png', $diamondChildren[1]['metadata']['image']);
        $this->assertEquals('fancy', $diamondChildren[1]['metadata']['group']);

        $this->assertEquals('Round', $diamondChildren[2]['title']);
        $this->assertEquals('/diamonds?shape=Round', $diamondChildren[2]['url']);

        // Assert dynamic compound IDs are present
        foreach ($diamondChildren as $child) {
            $this->assertNotNull($child['id']);
            $this->assertStringContainsString($shapeCategory->id . '_', $child['id']);
        }

        // 6. Assert Jewellery dynamic dropdown content and alphabetical sorting
        $jewelryChildren = $data[2]['children'];
        $this->assertCount(3, $jewelryChildren);

        // Sorting: Bracelet, Necklace, Ring
        $this->assertEquals('Bracelet', $jewelryChildren[0]['title']);
        $this->assertEquals('/jewelry?type=Bracelet', $jewelryChildren[0]['url']);
        
        $this->assertEquals('Necklace', $jewelryChildren[1]['title']);
        $this->assertEquals('/jewelry?type=Necklace', $jewelryChildren[1]['url']);
        
        $this->assertEquals('Ring', $jewelryChildren[2]['title']);
        $this->assertEquals('/jewelry?type=Ring', $jewelryChildren[2]['url']);

        // Assert dynamic compound IDs are present
        foreach ($jewelryChildren as $child) {
            $this->assertNotNull($child['id']);
            $this->assertStringContainsString($jewelryCategory->id . '_', $child['id']);
        }
    }

    /**
     * Test navigation structure when categories are not defined in the database.
     */
    public function test_navigation_handles_missing_categories_gracefully()
    {
        // Arrange: Explicitly delete categories to simulate missing data
        Category::whereIn('type', ['shape', 'jewelery_type'])->delete();

        // Act: Request the navigation API endpoint without these categories
        $response = $this->getJson('/api/storefront/navigation');

        // Assert: Verify endpoint works and returns empty arrays for children
        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        $this->assertCount(6, $data);
        $this->assertEquals('Diamonds', $data[1]['title']);
        $this->assertEmpty($data[1]['children']);
        $this->assertCount(0, $data[1]['children']);
        
        $this->assertEquals('Jewellery', $data[2]['title']);
        $this->assertEmpty($data[2]['children']);
        $this->assertCount(0, $data[2]['children']);
    }
}
