<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_inventory_reservations', function (Blueprint $table) {
            $table->foreignId('origin_store_id')->nullable()->after('shopify_store_id')->constrained('shopify_stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_inventory_reservations', function (Blueprint $table) {
            $table->dropForeign(['origin_store_id']);
            $table->dropColumn('origin_store_id');
        });
    }
};
