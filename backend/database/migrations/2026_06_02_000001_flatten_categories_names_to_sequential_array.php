<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $categories = DB::table('categories')->get();

        foreach ($categories as $category) {
            $names = json_decode($category->names, true);
            if (!is_array($names)) {
                continue;
            }

            $flattened = [];
            foreach ($names as $key => $item) {
                if (is_array($item)) {
                    $name = trim((string) ($item['name'] ?? (is_string($key) ? $key : '')));
                    $image = $item['image'] ?? null;
                    if ($name === '') {
                        continue;
                    }
                    if ($image !== null) {
                        $flattened[] = ['name' => $name, 'image' => $image];
                    } else {
                        $flattened[] = $name;
                    }
                } elseif (!is_numeric($key)) {
                    $name = trim((string) $key);
                    if ($name !== '') {
                        $flattened[] = $name;
                    }
                } else {
                    $name = trim((string) $item);
                    if ($name !== '') {
                        $flattened[] = $name;
                    }
                }
            }

            if ($flattened !== $names) {
                DB::table('categories')
                    ->where('id', $category->id)
                    ->update(['names' => json_encode($flattened)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No automatic reverse for flattened arrays.
    }
};
