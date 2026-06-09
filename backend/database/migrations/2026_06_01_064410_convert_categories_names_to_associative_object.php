<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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

        foreach ($categories as $cat) {
            $names = json_decode($cat->names, true) ?? [];
            
            // Check if it is a sequential array
            if (is_array($names) && (empty($names) || array_keys($names) === range(0, count($names) - 1))) {
                $newNames = [];
                foreach ($names as $item) {
                    $name = is_array($item) ? ($item['name'] ?? '') : $item;
                    $image = is_array($item) ? ($item['image'] ?? null) : null;
                    $name = trim($name);
                    if ($name !== '') {
                        $newNames[$name] = [
                            'name' => $name,
                            'image' => $image
                        ];
                    }
                }
                DB::table('categories')->where('id', $cat->id)->update([
                    'names' => json_encode($newNames)
                ]);
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
        $categories = DB::table('categories')->get();

        foreach ($categories as $cat) {
            $names = json_decode($cat->names, true) ?? [];

            // Check if it is an associative array (not sequential)
            if (is_array($names) && !empty($names) && array_keys($names) !== range(0, count($names) - 1)) {
                $newNames = [];
                foreach ($names as $key => $item) {
                    $name = is_array($item) ? ($item['name'] ?? $key) : $item;
                    $image = is_array($item) ? ($item['image'] ?? null) : null;
                    
                    if ($image) {
                        $newNames[] = [
                            'name' => $name,
                            'image' => $image
                        ];
                    } else {
                        $newNames[] = $name;
                    }
                }
                DB::table('categories')->where('id', $cat->id)->update([
                    'names' => json_encode($newNames)
                ]);
            }
        }
    }
};
