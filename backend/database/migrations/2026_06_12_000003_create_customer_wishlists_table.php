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
        Schema::create('customer_wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();
            $table->string('product_type'); // 'diamond' or 'jewellery'
            $table->unsignedBigInteger('product_id');
            $table->timestamps();

            // Unique composite index to prevent duplicate entries per customer + product
            $table->unique(['customer_id', 'product_type', 'product_id'], 'customer_wishlist_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_wishlists');
    }
};
