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
        // 1. Add indexes to diamonds table
        Schema::table('diamonds', function (Blueprint $table) {
            $table->index('updated_at', 'idx_diamonds_updated_at');
        });

        // 2. Add indexes to jeweleries table
        Schema::table('jeweleries', function (Blueprint $table) {
            $table->index('updated_at', 'idx_jeweleries_updated_at');
        });

        // 3. Add indexes to shopify_products table
        Schema::table('shopify_products', function (Blueprint $table) {
            $table->index('sync_status', 'idx_shopify_products_sync_status');
        });

        // 4. Add indexes to shopify_webhook_logs table
        Schema::table('shopify_webhook_logs', function (Blueprint $table) {
            $table->index('created_at', 'idx_webhook_logs_created_at');
        });

        // 5. Add indexes to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at', 'idx_orders_created_at');
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
            $table->dropIndex('idx_orders_created_at');
        });

        Schema::table('shopify_webhook_logs', function (Blueprint $table) {
            $table->dropIndex('idx_webhook_logs_created_at');
        });

        Schema::table('shopify_products', function (Blueprint $table) {
            $table->dropIndex('idx_shopify_products_sync_status');
        });

        Schema::table('jeweleries', function (Blueprint $table) {
            $table->dropIndex('idx_jeweleries_updated_at');
        });

        Schema::table('diamonds', function (Blueprint $table) {
            $table->dropIndex('idx_diamonds_updated_at');
        });
    }
};
