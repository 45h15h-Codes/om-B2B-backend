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
        Schema::table('shopify_products', function (Blueprint $table) {
            $table->foreignId('shopify_store_id')->nullable()->after('product_id')->constrained('shopify_stores')->onDelete('cascade');
            $table->string('product_reference_id')->nullable()->after('shopify_store_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_products', function (Blueprint $table) {
            $table->dropForeign(['shopify_store_id']);
            $table->dropColumn(['shopify_store_id', 'product_reference_id']);
        });
    }
};
