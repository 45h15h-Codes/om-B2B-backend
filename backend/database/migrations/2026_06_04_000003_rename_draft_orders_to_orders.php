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
        // Drop foreign key constraints on draft_order_logs table first
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            Schema::table('draft_order_logs', function (Blueprint $table) {
                $table->dropForeign(['draft_order_id']);
            });
        }

        // Rename tables
        Schema::rename('draft_orders', 'orders');
        Schema::rename('draft_order_logs', 'order_logs');

        // Modify orders table (add columns and indexes)
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('email');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->string('shopify_order_id')->nullable()->after('shopify_draft_id');
            $table->string('shopify_order_number')->nullable()->after('shopify_order_id');
            $table->string('shopify_order_admin_url')->nullable()->after('shopify_order_number');
            $table->timestamp('invoice_sent_at')->nullable()->after('invoice_url');
            $table->json('shopify_store_snapshot')->nullable()->after('shopify_store_id');
            $table->softDeletes()->after('updated_at');

            // Unique/Index additions
            $table->unique('shopify_draft_id');
            $table->index('shopify_order_id');
            $table->index('status');
        });

        // Rename draft_order_id column on order_logs table
        Schema::table('order_logs', function (Blueprint $table) {
            $table->renameColumn('draft_order_id', 'order_id');
        });

        // Recreate foreign key constraint
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            Schema::table('order_logs', function (Blueprint $table) {
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop foreign key
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            Schema::table('order_logs', function (Blueprint $table) {
                $table->dropForeign(['order_id']);
            });
        }

        // Rename column back
        Schema::table('order_logs', function (Blueprint $table) {
            $table->renameColumn('order_id', 'draft_order_id');
        });

        // Remove indexes and columns from orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['shopify_draft_id']);
            $table->dropIndex(['shopify_order_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'customer_name',
                'customer_phone',
                'shopify_order_id',
                'shopify_order_number',
                'shopify_order_admin_url',
                'invoice_sent_at',
                'shopify_store_snapshot',
                'deleted_at'
            ]);
        });

        // Rename tables back
        Schema::rename('orders', 'draft_orders');
        Schema::rename('order_logs', 'draft_order_logs');

        // Recreate foreign key back
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            Schema::table('draft_order_logs', function (Blueprint $table) {
                $table->foreign('draft_order_id')->references('id')->on('draft_orders')->onDelete('cascade');
            });
        }
    }
};
