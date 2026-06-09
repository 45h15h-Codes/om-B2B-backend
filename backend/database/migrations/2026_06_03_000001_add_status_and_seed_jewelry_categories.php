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
        // 1. Add status column to jeweleries table
        Schema::table('jeweleries', function (Blueprint $table) {
            $table->string('status')->default('Approved')->after('created_by');
        });

        // 2. Seed jewelry categories
        $jewelryCategories = [
            'jewelery_type' => ['Ring', 'Bracelet', 'Earings', 'Necklace', 'Watch', 'Pendent'],
            'metal_type' => ['Gold', 'Platinum', 'Silver', 'Rose Gold'],
            'metal_karat' => ['14 KT', '18 KT', '22 KT', '950 Plat'],
            'gemstone_type' => ['Diamond', 'Sapphire', 'Ruby', 'Emerald']
        ];

        foreach ($jewelryCategories as $type => $names) {
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 1. Remove seeded categories
        DB::table('categories')
            ->whereIn('type', ['jewelery_type', 'metal_type', 'metal_karat', 'gemstone_type'])
            ->delete();

        // 2. Remove status column from jeweleries table
        Schema::table('jeweleries', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
