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
        // 1. Assign temp unique stock_no for null/empty values
        $emptyDiamonds = DB::table('diamonds')
            ->whereNull('stock_no')
            ->orWhere('stock_no', '')
            ->get();

        foreach ($emptyDiamonds as $d) {
            DB::table('diamonds')
                ->where('id', $d->id)
                ->update(['stock_no' => 'DIA-TEMP-' . $d->id]);
        }

        // 2. Detect and resolve duplicates
        $duplicateStockNos = DB::table('diamonds')
            ->select('stock_no')
            ->groupBy('stock_no')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('stock_no');

        foreach ($duplicateStockNos as $stockNo) {
            $records = DB::table('diamonds')
                ->where('stock_no', $stockNo)
                ->orderBy('id', 'asc')
                ->get();

            // Identify the master record (prefer one that is mapped to Shopify)
            $master = null;
            foreach ($records as $record) {
                $isMapped = DB::table('shopify_products')
                    ->where('product_type', 'diamond')
                    ->where('product_id', $record->id)
                    ->exists();
                if ($isMapped) {
                    $master = $record;
                    break;
                }
            }

            if (!$master) {
                $master = $records[0]; // fallback to the oldest one
            }

            // Remap references for duplicates and delete them
            foreach ($records as $record) {
                if ($record->id === $master->id) {
                    continue;
                }

                // Update shopify_products product_id
                DB::table('shopify_products')
                    ->where('product_type', 'diamond')
                    ->where('product_id', $record->id)
                    ->update(['product_id' => $master->id]);

                // Update shopify_inventory_reservations product_id
                DB::table('shopify_inventory_reservations')
                    ->where('product_id', $record->id)
                    ->whereIn('product_type', ['diamond', 'App\\Models\\Diamond'])
                    ->update(['product_id' => $master->id]);

                // Delete the duplicate record
                DB::table('diamonds')
                    ->where('id', $record->id)
                    ->delete();
            }
        }

        // 3. Make stock_no unique and not nullable
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE diamonds MODIFY stock_no VARCHAR(255) NOT NULL");
        }
        Schema::table('diamonds', function (Blueprint $table) {
            $table->unique('stock_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('diamonds', function (Blueprint $table) {
            $table->dropUnique(['stock_no']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE diamonds MODIFY stock_no VARCHAR(255) NULL");
        }
    }
};
