<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Diamond;
use App\Models\Jewelery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MediaUploadTest extends TestCase
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

    public function test_diamond_multiple_media_upload_and_removal()
    {
        Storage::fake('public');
        $user = $this->getAdminUser('normal_admin');

        // 1. Create a diamond with multiple images and videos
        $image1 = UploadedFile::fake()->create('dia_img1.jpg', 100, 'image/jpeg');
        $image2 = UploadedFile::fake()->create('dia_img2.png', 100, 'image/png');
        $video1 = UploadedFile::fake()->create('dia_vid1.mp4', 500, 'video/mp4');

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post(route('diamonds.store'), [
                'stock_no' => 'DIA-UPLOAD-101',
                'shape' => 'Round',
                'size' => 1.05,
                'color' => 'G',
                'clarity' => 'SI1',
                'asking_price' => 2500,
                'images' => [$image1, $image2],
                'videos' => [$video1]
            ]);

        $response->assertRedirect(route('diamonds.index'));
        $response->assertSessionHas('success');

        $diamond = Diamond::where('stock_no', 'DIA-UPLOAD-101')->firstOrFail();
        $this->assertCount(2, $diamond->images);
        $this->assertCount(1, $diamond->videos);

        // Verify storage exists
        Storage::disk('public')->assertExists($diamond->images[0]);
        Storage::disk('public')->assertExists($diamond->images[1]);
        Storage::disk('public')->assertExists($diamond->videos[0]);

        $pathToRemove = $diamond->images[0];
        $pathToKeep = $diamond->images[1];
        $videoToRemove = $diamond->videos[0];

        // 2. Update diamond: remove one image and one video, and upload a new image and new video
        $newImage = UploadedFile::fake()->create('dia_new_img.jpg', 100, 'image/jpeg');
        $newVideo = UploadedFile::fake()->create('dia_new_vid.mp4', 1000, 'video/mp4');

        $responseUpdate = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->put(route('diamonds.update', $diamond), [
                'stock_no' => 'DIA-UPLOAD-101',
                'shape' => 'Round',
                'size' => 1.05,
                'color' => 'G',
                'clarity' => 'SI1',
                'asking_price' => 2500,
                'remove_images' => [$pathToRemove],
                'remove_videos' => [$videoToRemove],
                'images' => [$newImage],
                'videos' => [$newVideo]
            ]);

        $responseUpdate->assertRedirect();
        
        $diamond->refresh();
        $this->assertCount(2, $diamond->images); // $pathToKeep + $newImage
        $this->assertCount(1, $diamond->videos); // $newVideo

        Storage::disk('public')->assertMissing($pathToRemove);
        Storage::disk('public')->assertMissing($videoToRemove);
        Storage::disk('public')->assertExists($pathToKeep);
        Storage::disk('public')->assertExists($diamond->images[1]);
        Storage::disk('public')->assertExists($diamond->videos[0]);
    }

    public function test_jewelery_multiple_media_upload_and_removal()
    {
        Storage::fake('public');
        $user = $this->getAdminUser('normal_admin');

        // 1. Create a jewellery item with multiple images and videos
        $image1 = UploadedFile::fake()->create('jw_img1.jpg', 100, 'image/jpeg');
        $image2 = UploadedFile::fake()->create('jw_img2.png', 100, 'image/png');
        $video1 = UploadedFile::fake()->create('jw_vid1.mp4', 500, 'video/mp4');

        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post(route('jewelery.store'), [
                'sku' => 'JW-UPLOAD-101',
                'name' => 'Premium Diamond Ring',
                'type' => 'Ring',
                'price' => 4500,
                'location' => 'Mumbai',
                'images' => [$image1, $image2],
                'videos' => [$video1]
            ]);

        $response->assertRedirect(route('jewelery.index'));
        $response->assertSessionHas('success');

        $jewelery = Jewelery::where('sku', 'JW-UPLOAD-101')->firstOrFail();
        $this->assertCount(2, $jewelery->images);
        $this->assertCount(1, $jewelery->videos);

        // Verify storage exists
        Storage::disk('public')->assertExists($jewelery->images[0]);
        Storage::disk('public')->assertExists($jewelery->images[1]);
        Storage::disk('public')->assertExists($jewelery->videos[0]);

        // image_url is populated with the first image's path as legacy fallback
        $this->assertStringContainsString($jewelery->images[0], $jewelery->image_url);

        $pathToRemove = $jewelery->images[0];
        $pathToKeep = $jewelery->images[1];
        $videoToRemove = $jewelery->videos[0];

        // 2. Update jewellery: remove one image and one video, and upload a new image and new video
        $newImage = UploadedFile::fake()->create('jw_new_img.jpg', 100, 'image/jpeg');
        $newVideo = UploadedFile::fake()->create('jw_new_vid.mp4', 1000, 'video/mp4');

        $responseUpdate = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->put(route('jewelery.update', $jewelery), [
                'sku' => 'JW-UPLOAD-101',
                'name' => 'Premium Diamond Ring',
                'type' => 'Ring',
                'price' => 4500,
                'location' => 'Mumbai',
                'remove_images' => [$pathToRemove],
                'remove_videos' => [$videoToRemove],
                'images' => [$newImage],
                'videos' => [$newVideo]
            ]);

        $responseUpdate->assertRedirect(route('jewelery.index'));
        
        $jewelery->refresh();
        $this->assertCount(2, $jewelery->images); // $pathToKeep + $newImage
        $this->assertCount(1, $jewelery->videos); // $newVideo

        Storage::disk('public')->assertMissing($pathToRemove);
        Storage::disk('public')->assertMissing($videoToRemove);
        Storage::disk('public')->assertExists($pathToKeep);
        Storage::disk('public')->assertExists($jewelery->images[1]);
        Storage::disk('public')->assertExists($jewelery->videos[0]);
    }

    public function test_storefront_apis_format_media_urls_correctly()
    {
        // 1. Approved available Diamond with images and videos
        $diamond = Diamond::create([
            'stock_no' => 'DIA-API-TEST',
            'shape' => 'Round Brilliant',
            'size' => 1.25,
            'color' => 'E',
            'clarity' => 'VS1',
            'asking_price' => 5000,
            'status' => Diamond::STATUS_APPROVED,
            'inventory_status' => 'available',
            'images' => ['diamonds/images/dia1.jpg', 'diamonds/images/dia2.jpg'],
            'videos' => ['diamonds/videos/dia1.mp4'],
        ]);

        // Detail api
        $response = $this->getJson("/api/storefront/diamonds/{$diamond->id}");
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data['images']);
        $this->assertEquals(asset('storage/diamonds/images/dia1.jpg'), $data['images'][0]);
        $this->assertEquals(asset('storage/diamonds/images/dia2.jpg'), $data['images'][1]);
        $this->assertEquals(asset('storage/diamonds/videos/dia1.mp4'), $data['video']);
        $this->assertCount(1, $data['videos']);
        $this->assertEquals(asset('storage/diamonds/videos/dia1.mp4'), $data['videos'][0]);

        // Listing api
        $listResponse = $this->getJson("/api/storefront/diamonds");
        $listResponse->assertStatus(200);
        $listItem = collect($listResponse->json('data'))->firstWhere('sku', 'DIA-API-TEST');
        $this->assertNotNull($listItem);
        $this->assertEquals(asset('storage/diamonds/images/dia1.jpg'), $listItem['image']);

        // 2. Approved available Jewellery with images and videos
        $jewelery = Jewelery::create([
            'sku' => 'JW-API-TEST',
            'name' => 'Rose Gold Necklace',
            'type' => 'Necklace',
            'price' => 1500,
            'location' => 'Surat',
            'status' => Jewelery::STATUS_APPROVED,
            'inventory_status' => 'available',
            'images' => ['jewelleries/images/jw1.jpg', 'jewelleries/images/jw2.jpg'],
            'videos' => ['jewelleries/videos/jw1.mp4'],
        ]);

        // Detail api
        $responseJw = $this->getJson("/api/storefront/jewellery/{$jewelery->id}");
        $responseJw->assertStatus(200);
        $dataJw = $responseJw->json('data');

        $this->assertCount(2, $dataJw['images']);
        $this->assertEquals(asset('storage/jewelleries/images/jw1.jpg'), $dataJw['images'][0]);
        $this->assertEquals(asset('storage/jewelleries/images/jw2.jpg'), $dataJw['images'][1]);
        $this->assertCount(1, $dataJw['videos']);
        $this->assertEquals(asset('storage/jewelleries/videos/jw1.mp4'), $dataJw['videos'][0]);

        // Listing api
        $listResponseJw = $this->getJson("/api/storefront/jewellery");
        $listResponseJw->assertStatus(200);
        $listItemJw = collect($listResponseJw->json('data'))->firstWhere('sku', 'JW-API-TEST');
        $this->assertNotNull($listItemJw);
        $this->assertEquals(asset('storage/jewelleries/images/jw1.jpg'), $listItemJw['image']);
    }

    public function test_duplicate_diamond_prevention_on_customer_website()
    {
        $user = $this->getAdminUser('normal_admin');

        // 1. Create a diamond visible on Customer Website with report_no 'GIA-DUP-001'
        $diamond1 = Diamond::create([
            'stock_no' => 'DIA-FIRST',
            'shape' => 'Round',
            'size' => 1.00,
            'color' => 'D',
            'clarity' => 'FL',
            'asking_price' => 5000,
            'show_on_OM' => true,
            'report_no' => 'GIA-DUP-001'
        ]);

        // 2. Try to create another diamond visible on Customer Website with the same report_no
        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post(route('diamonds.store'), [
                'stock_no' => 'DIA-SECOND',
                'shape' => 'Round',
                'size' => 1.00,
                'color' => 'D',
                'clarity' => 'FL',
                'asking_price' => 6000,
                'show_on_OM' => '1',
                'report_no' => 'GIA-DUP-001'
            ]);

        // Should prevent the upload and redirect back with the error message
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Diamond is already uploaded.');

        // Verify that the second record was NOT created
        $this->assertDatabaseMissing('diamonds', ['stock_no' => 'DIA-SECOND']);

        // 3. Create another diamond NOT visible on Customer Website (show_on_OM = false) with the same report_no
        $response2 = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->post(route('diamonds.store'), [
                'stock_no' => 'DIA-THIRD',
                'shape' => 'Round',
                'size' => 1.00,
                'color' => 'D',
                'clarity' => 'FL',
                'asking_price' => 7000,
                'report_no' => 'GIA-DUP-001'
            ]);

        // Verify the record was created
        $diamond3 = Diamond::where('stock_no', 'DIA-THIRD')->firstOrFail();
        $this->assertFalse($diamond3->show_on_OM);
        $this->assertEquals('GIA-DUP-001', $diamond3->report_no);

        // 4. Update the third diamond to be visible on Customer Website (show_on_OM = true)
        // This should fail because the first diamond already has show_on_OM = true and same report_no
        $response3 = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->put(route('diamonds.update', $diamond3), [
                'stock_no' => 'DIA-THIRD',
                'shape' => 'Round',
                'size' => 1.00,
                'color' => 'D',
                'clarity' => 'FL',
                'asking_price' => 7000,
                'show_on_OM' => '1',
                'report_no' => 'GIA-DUP-001'
            ]);

        $response3->assertRedirect();
        $response3->assertSessionHas('error', 'Diamond is already uploaded.');
        
        $diamond3->refresh();
        $this->assertFalse($diamond3->show_on_OM); // show_on_OM remains false
    }

    public function test_duplicate_diamond_prevention_on_spreadsheet_import()
    {
        $user = $this->getAdminUser('normal_admin');

        // Create an existing Customer Website diamond
        Diamond::create([
            'stock_no' => 'DIA-EXISTING-1',
            'shape' => 'Round',
            'size' => 1.00,
            'color' => 'D',
            'clarity' => 'FL',
            'asking_price' => 5000,
            'show_on_OM' => true,
            'report_no' => 'GIA-IMPORT-DUP'
        ]);

        // Set up chunks of CSV data
        $chunk = [
            [
                'stock_no' => 'DIA-IMPORT-NEW',
                'shape' => 'Round',
                'size' => '1.00',
                'color' => 'D',
                'clarity' => 'FL',
                'asking_price' => '6000',
                'show_on_OM' => 'true',
                'report_no' => 'GIA-IMPORT-DUP' // Duplicate certificate number
            ]
        ];

        $importHistory = \App\Models\ImportHistory::create([
            'user_id' => $user->id,
            'file_name' => 'test_import.csv',
            'file_path' => 'imports/test_import.csv',
            'import_type' => 'diamonds',
            'total_rows' => 1,
            'status' => 'processing',
            'pending_chunks' => 1,
        ]);

        $meta = [
            'created_by' => 'Normal Admin',
            'status' => 'Pending'
        ];

        $job = new \App\Jobs\ProcessImportChunkJob($chunk, $importHistory->id, 'diamonds', $meta, $user->id);
        $job->handle();

        $importHistory->refresh();
        $this->assertEquals(0, $importHistory->successful_rows);
        $this->assertEquals(1, $importHistory->failed_rows);

        $errorLog = $importHistory->error_log;
        $this->assertCount(1, $errorLog);
        $this->assertEquals('Diamond is already uploaded.', $errorLog[0]['error']);
    }

    public function test_duplicate_diamond_prevention_at_database_level()
    {
        // 1. Create first diamond with show_on_OM = true and report_no = 'DB-DUP'
        Diamond::create([
            'stock_no' => 'DIA-DB-1',
            'shape' => 'Round',
            'size' => 1.00,
            'color' => 'D',
            'clarity' => 'FL',
            'asking_price' => 5000,
            'show_on_OM' => true,
            'report_no' => 'DB-DUP'
        ]);

        // 2. Expect QueryException when creating another diamond with show_on_OM = true and same report_no
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Turn off model events to directly hit DB write and check the UNIQUE constraint
        Diamond::flushEventListeners();

        // Directly insert duplicate row using Query Builder
        \Illuminate\Support\Facades\DB::table('diamonds')->insert([
            'stock_no' => 'DIA-DB-2',
            'shape' => 'Round',
            'size' => 1.00,
            'color' => 'D',
            'clarity' => 'FL',
            'asking_price' => 6000,
            'show_on_OM' => true,
            'customer_website_report_no' => 'DB-DUP', // Same unique key
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
