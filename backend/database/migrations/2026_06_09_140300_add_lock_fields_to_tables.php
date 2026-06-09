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
        Schema::table('diamonds', function (Blueprint $table) {
            $table->unsignedBigInteger('hold_shopify_store_id')->nullable()->after('hold_at');
            $table->foreign('hold_shopify_store_id')->references('id')->on('shopify_stores')->onDelete('set null');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique(['shopify_store_id', 'shopify_order_id'], 'orders_shopify_store_id_shopify_order_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_shopify_store_id_shopify_order_id_unique');
        });

        Schema::table('diamonds', function (Blueprint $table) {
            $table->dropForeign(['hold_shopify_store_id']);
            $table->dropColumn('hold_shopify_store_id');
        });
    }
};
