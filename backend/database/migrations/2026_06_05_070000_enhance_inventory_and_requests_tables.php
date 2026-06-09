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
        // 1. Update diamonds table
        Schema::table('diamonds', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_admin_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('hold_by')->nullable()->after('inventory_status');
            $table->text('hold_reason')->nullable()->after('hold_by');
            $table->timestamp('hold_at')->nullable()->after('hold_reason');

            // Foreign keys & indexes
            $table->foreign('assigned_admin_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('hold_by')->references('id')->on('users')->onDelete('set null');
            $table->index('assigned_admin_id');
        });

        // 2. Update jeweleries table
        Schema::table('jeweleries', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_admin_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('hold_by')->nullable()->after('inventory_status');
            $table->text('hold_reason')->nullable()->after('hold_by');
            $table->timestamp('hold_at')->nullable()->after('hold_reason');

            // Foreign keys & indexes
            $table->foreign('assigned_admin_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('hold_by')->references('id')->on('users')->onDelete('set null');
            $table->index('assigned_admin_id');
        });

        // 3. Create inventory_requests table
        Schema::create('inventory_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('request_type');
            $table->string('product_type')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('action_payload')->nullable();
            $table->string('priority')->default('Medium');
            $table->string('status')->default('Pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys & indexes
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index('user_id');
            $table->index('status');
            $table->index('request_type');
        });

        // 4. Create inventory_histories table
        Schema::create('inventory_histories', function (Blueprint $table) {
            $table->id();
            $table->string('product_type');
            $table->unsignedBigInteger('product_id');
            $table->string('action');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('remarks')->nullable();
            $table->string('ip_address')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys & indexes
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['product_type', 'product_id']);
            $table->index('action');
        });

        // 5. Create failed_inventory_syncs table
        Schema::create('failed_inventory_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('product_type');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('shopify_store_id');
            $table->text('error_message');
            $table->integer('retry_count')->default(0);
            $table->string('status')->default('failed');
            $table->timestamps();

            $table->index(['product_type', 'product_id']);
            $table->index('shopify_store_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('failed_inventory_syncs');
        Schema::dropIfExists('inventory_histories');
        Schema::dropIfExists('inventory_requests');

        Schema::table('jeweleries', function (Blueprint $table) {
            $table->dropForeign(['assigned_admin_id']);
            $table->dropForeign(['hold_by']);
            $table->dropColumn(['assigned_admin_id', 'hold_by', 'hold_reason', 'hold_at']);
        });

        Schema::table('diamonds', function (Blueprint $table) {
            $table->dropForeign(['assigned_admin_id']);
            $table->dropForeign(['hold_by']);
            $table->dropColumn(['assigned_admin_id', 'hold_by', 'hold_reason', 'hold_at']);
        });
    }
};
