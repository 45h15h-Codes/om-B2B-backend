<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Jewelery;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JewelerySampleCsvTest extends TestCase
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
     * Test download link presence in view.
     */
    public function test_jewelry_bulk_import_has_download_sample_link()
    {
        $user = $this->getAdminUser('normal_admin');
        $response = $this->actingAs($user)->get(route('jewelery.index', ['tab' => 'upload']));

        $response->assertStatus(200);
        $response->assertSee('samples/jewellery_sample.csv');
    }

    /**
     * Test sample CSV exists and has correct columns and data.
     */
    public function test_sample_csv_exists_and_contains_expected_headers()
    {
        $filePath = public_path('samples/jewellery_sample.csv');
        $this->assertFileExists($filePath);

        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle);
        fclose($handle);

        $this->assertEquals([
            'name',
            'sku',
            'type',
            'description',
            'price',
            'metal_type',
            'total_weight',
            'image'
        ], $headers);
    }

    /**
     * Test importing via jewellery_sample.csv structure is compatible with import logic.
     */
    public function test_importing_jewelry_sample_csv_format_is_compatible()
    {
        $user = $this->getAdminUser('normal_admin');

        // Create a temporary CSV using the exact sample format
        $csvContent = "name,sku,type,description,price,metal_type,total_weight,image\n";
        $csvContent .= "Test Ring,TEST-R-01,Ring,Beautiful Ring,450.00,Platinum,3.5,https://example.com/test_ring.jpg\n";
        $csvContent .= "Test Bracelet,TEST-B-01,Bracelet,Gold Bracelet,950.00,Gold,12.2,https://example.com/test_bracelet.jpg\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'test_jewel_sample_csv');
        file_put_contents($tempFile, $csvContent);

        $uploadedFile = new UploadedFile(
            $tempFile,
            'jewellery_sample.csv',
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

        $item1 = Jewelery::where('sku', 'TEST-R-01')->first();
        $this->assertNotNull($item1);
        $this->assertEquals('Test Ring', $item1->name);
        $this->assertEquals('Ring', $item1->type);
        $this->assertEquals(450.00, (float) $item1->price);
        $this->assertEquals('https://example.com/test_ring.jpg', $item1->image_url);
        $this->assertEquals('Beautiful Ring', $item1->description);
        $this->assertEquals('Platinum', $item1->metal_type);
        $this->assertEquals(3.5, (float) $item1->total_weight);
        $this->assertEquals('Normal Admin', $item1->created_by);
        $this->assertEquals('Pending', $item1->status);

        $item2 = Jewelery::where('sku', 'TEST-B-01')->first();
        $this->assertNotNull($item2);
        $this->assertEquals('Test Bracelet', $item2->name);
        $this->assertEquals('Bracelet', $item2->type);
        $this->assertEquals(950.00, (float) $item2->price);
        $this->assertEquals('https://example.com/test_bracelet.jpg', $item2->image_url);
        $this->assertEquals('Gold Bracelet', $item2->description);
        $this->assertEquals('Gold', $item2->metal_type);
        $this->assertEquals(12.2, (float) $item2->total_weight);
        $this->assertEquals('Normal Admin', $item2->created_by);
        $this->assertEquals('Pending', $item2->status);

        @unlink($tempFile);
    }
}
