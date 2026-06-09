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
            $table->unsignedBigInteger('order_id')->nullable()->after('hold_at');
            $table->string('shopify_order_id')->nullable()->after('order_id');
            $table->unsignedBigInteger('sold_store_id')->nullable()->after('shopify_order_id');
            $table->timestamp('sold_at')->nullable()->after('sold_store_id');
            $table->unsignedBigInteger('sold_by_store_id')->nullable()->after('sold_at');
            $table->string('sold_by_store_name')->nullable()->after('sold_by_store_id');
            $table->unsignedBigInteger('sold_by_user_id')->nullable()->after('sold_by_store_name');
            $table->string('sold_order_number')->nullable()->after('sold_by_user_id');
            $table->timestamp('sold_order_date')->nullable()->after('sold_order_number');

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('sold_store_id')->references('id')->on('shopify_stores')->onDelete('set null');
            $table->foreign('sold_by_store_id')->references('id')->on('shopify_stores')->onDelete('set null');
            $table->foreign('sold_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('shopify_order_id');
        });

        Schema::table('jeweleries', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('hold_at');
            $table->string('shopify_order_id')->nullable()->after('order_id');
            $table->unsignedBigInteger('sold_store_id')->nullable()->after('shopify_order_id');
            $table->timestamp('sold_at')->nullable()->after('sold_store_id');
            $table->unsignedBigInteger('sold_by_store_id')->nullable()->after('sold_at');
            $table->string('sold_by_store_name')->nullable()->after('sold_by_store_id');
            $table->unsignedBigInteger('sold_by_user_id')->nullable()->after('sold_by_store_name');
            $table->string('sold_order_number')->nullable()->after('sold_by_user_id');
            $table->timestamp('sold_order_date')->nullable()->after('sold_order_number');

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('sold_store_id')->references('id')->on('shopify_stores')->onDelete('set null');
            $table->foreign('sold_by_store_id')->references('id')->on('shopify_stores')->onDelete('set null');
            $table->foreign('sold_by_user_id')->references('id')->on('users')->onDelete('set null');
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
        Schema::table('jeweleries', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['sold_store_id']);
            $table->dropForeign(['sold_by_store_id']);
            $table->dropForeign(['sold_by_user_id']);
            $table->dropIndex(['shopify_order_id']);
            $table->dropColumn([
                'order_id', 'shopify_order_id', 'sold_store_id', 'sold_at',
                'sold_by_store_id', 'sold_by_store_name', 'sold_by_user_id',
                'sold_order_number', 'sold_order_date'
            ]);
        });

        Schema::table('diamonds', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['sold_store_id']);
            $table->dropForeign(['sold_by_store_id']);
            $table->dropForeign(['sold_by_user_id']);
            $table->dropIndex(['shopify_order_id']);
            $table->dropColumn([
                'order_id', 'shopify_order_id', 'sold_store_id', 'sold_at',
                'sold_by_store_id', 'sold_by_store_name', 'sold_by_user_id',
                'sold_order_number', 'sold_order_date'
            ]);
        });
    }
};
