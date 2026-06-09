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
        // Add inventory_status to diamonds
        Schema::table('diamonds', function (Blueprint $table) {
            $table->string('inventory_status')->default('available')->after('status');
            $table->index('inventory_status');
        });

        // Add inventory_status to jeweleries
        Schema::table('jeweleries', function (Blueprint $table) {
            $table->string('inventory_status')->default('available')->after('status');
            $table->index('inventory_status');
        });

        // Add webhook_secret to shopify_stores
        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->string('webhook_secret')->nullable()->after('access_token');
        });

        // Create shopify_webhook_logs
        Schema::create('shopify_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('webhook_id')->unique();
            $table->string('topic');
            $table->string('shop_domain');
            $table->json('payload');
            $table->string('status')->default('pending'); // pending, processed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // Create shopify_inventory_reservations
        Schema::create('shopify_inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('product_type'); // diamond or jewelry
            $table->unsignedBigInteger('product_id');
            $table->foreignId('shopify_store_id')->constrained('shopify_stores')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('shopify_order_id')->nullable();
            $table->string('status')->default('hold'); // hold, released, completed
            $table->timestamps();

            // Indexes
            $table->index(['product_type', 'product_id', 'status'], 'idx_product_reservation_status');
            $table->index('shopify_order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shopify_inventory_reservations');
        Schema::dropIfExists('shopify_webhook_logs');

        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->dropColumn('webhook_secret');
        });

        Schema::table('jeweleries', function (Blueprint $table) {
            $table->dropColumn('inventory_status');
        });

        Schema::table('diamonds', function (Blueprint $table) {
            $table->dropColumn('inventory_status');
        });
    }
};
