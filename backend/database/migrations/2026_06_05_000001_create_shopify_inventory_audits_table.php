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
        Schema::create('shopify_inventory_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopify_store_id')->constrained('shopify_stores')->onDelete('cascade');
            $table->foreignId('diamond_id')->nullable()->constrained('diamonds')->onDelete('set null');
            $table->string('stock_no');
            $table->string('action'); // lock, release, sync
            $table->string('shopify_product_id')->nullable();
            $table->string('shopify_variant_id')->nullable();
            $table->integer('previous_quantity')->nullable();
            $table->integer('new_quantity');
            $table->json('api_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shopify_inventory_audits');
    }
};
