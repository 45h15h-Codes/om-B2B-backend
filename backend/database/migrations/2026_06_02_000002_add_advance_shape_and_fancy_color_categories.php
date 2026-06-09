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
        // 1. Create and seed the new category types
        $newCategories = [
            'advance_shape_detail' => ['Cut Corner', 'Modified', 'Brilliant', 'Step Cut', 'Rose Cut'],
            'fancy_color_intensity' => ['Faint', 'Very Light', 'Light', 'Fancy', 'Fancy Intense', 'Fancy Vivid'],
            'fancy_color_overtone' => ['None', 'Yellowish', 'Brownish', 'Pinkish'],
            'fancy_color_color' => ['Yellow', 'Pink', 'Blue', 'Green', 'Orange', 'Brown']
        ];

        foreach ($newCategories as $type => $names) {
            $exists = DB::table('categories')->where('type', $type)->exists();
            if (!$exists) {
                DB::table('categories')->insert([
                    'type' => $type,
                    'names' => json_encode($names),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // 2. Update existing 'shape' options to set group = 'basic'
        $shapeCat = DB::table('categories')->where('type', 'shape')->first();
        if ($shapeCat) {
            $names = json_decode($shapeCat->names, true);
            if (is_array($names)) {
                $updatedNames = [];
                foreach ($names as $item) {
                    if (is_array($item)) {
                        $item['group'] = $item['group'] ?? 'basic';
                        $updatedNames[] = $item;
                    } else {
                        $updatedNames[] = [
                            'name' => trim((string)$item),
                            'group' => 'basic'
                        ];
                    }
                }
                DB::table('categories')
                    ->where('id', $shapeCat->id)
                    ->update(['names' => json_encode($updatedNames)]);
            }
        }

        // 3. Update existing 'color' options to set groups ('white' vs 'fancy')
        $colorCat = DB::table('categories')->where('type', 'color')->first();
        if ($colorCat) {
            $names = json_decode($colorCat->names, true);
            if (is_array($names)) {
                $updatedNames = [];
                foreach ($names as $item) {
                    $nameStr = is_array($item) ? ($item['name'] ?? '') : $item;
                    $nameStr = trim((string)$nameStr);
                    
                    $group = (strcasecmp($nameStr, 'Fancy') === 0) ? 'fancy' : 'white';
                    
                    if (is_array($item)) {
                        $item['group'] = $item['group'] ?? $group;
                        $updatedNames[] = $item;
                    } else {
                        $updatedNames[] = [
                            'name' => $nameStr,
                            'group' => $group
                        ];
                    }
                }
                DB::table('categories')
                    ->where('id', $colorCat->id)
                    ->update(['names' => json_encode($updatedNames)]);
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
        // Delete the created categories
        DB::table('categories')
            ->whereIn('type', [
                'advance_shape_detail',
                'fancy_color_intensity',
                'fancy_color_overtone',
                'fancy_color_color'
            ])
            ->delete();

        // Optional: Revert groups in shape and color (not strictly necessary but nice to have)
        foreach (['shape', 'color'] as $type) {
            $cat = DB::table('categories')->where('type', $type)->first();
            if ($cat) {
                $names = json_decode($cat->names, true);
                if (is_array($names)) {
                    $revertedNames = [];
                    foreach ($names as $item) {
                        if (is_array($item)) {
                            if (isset($item['image'])) {
                                unset($item['group']);
                                $revertedNames[] = $item;
                            } else {
                                $revertedNames[] = $item['name'];
                            }
                        } else {
                            $revertedNames[] = $item;
                        }
                    }
                    DB::table('categories')
                        ->where('id', $cat->id)
                        ->update(['names' => json_encode($revertedNames)]);
                }
            }
        }
    }
};
