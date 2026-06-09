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
        Schema::create('shopify_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopify_store_id')->constrained('shopify_stores')->onDelete('cascade');
            $table->string('shopify_product_id');
            $table->string('shopify_variant_id');
            $table->string('inventory_item_id');
            $table->string('sku')->nullable();
            $table->integer('available')->default(0);
            $table->timestamps();

            $table->unique(['shopify_store_id', 'shopify_variant_id'], 'store_variant_unique');
            $table->index('inventory_item_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shopify_inventories');
    }
};
