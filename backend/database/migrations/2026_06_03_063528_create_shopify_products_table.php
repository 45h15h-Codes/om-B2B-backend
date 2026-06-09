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
        Schema::create('shopify_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_type'); // diamond or jewelry
            $table->unsignedBigInteger('product_id'); // Polymorphic ID
            $table->string('shopify_product_id')->nullable()->unique();
            $table->string('shopify_variant_id')->nullable();
            $table->string('shopify_product_url')->nullable();
            $table->string('sync_status')->default('pending'); // pending, processing, synced, failed
            $table->integer('sync_attempts')->default(0);
            $table->text('sync_message')->nullable();
            $table->json('response')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Indexes for faster lookups and polymorphic relations
            $table->index(['product_type', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shopify_products');
    }
};
