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
        Schema::table('shopify_orders', function (Blueprint $table) {
            // Drop unique index on shopify_order_id
            $table->dropUnique(['shopify_order_id']);
            
            // Add shopify_store_id column
            $table->foreignId('shopify_store_id')->nullable()->after('id')->constrained('shopify_stores')->onDelete('cascade');
            
            // Add composite unique index
            $table->unique(['shopify_store_id', 'shopify_order_id'], 'shopify_orders_store_order_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_orders', function (Blueprint $table) {
            $table->dropUnique('shopify_orders_store_order_unique');
            $table->dropForeign(['shopify_store_id']);
            $table->dropColumn('shopify_store_id');
            $table->unique('shopify_order_id');
        });
    }
};
