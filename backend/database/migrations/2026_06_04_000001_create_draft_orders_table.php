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
        Schema::create('draft_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shopify_store_id')->constrained('shopify_stores')->onDelete('cascade');
            $table->string('customer_id')->nullable();
            $table->string('email')->nullable();
            $table->json('items'); // JSON array of items with immutable snapshots
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2)->default(0.00);
            $table->string('status')->default('pending'); // pending, approved, pending_sync, syncing, synced, invoice_sent, completed, failed
            $table->string('shopify_draft_id')->nullable();
            $table->text('invoice_url')->nullable();
            $table->json('shopify_payload')->nullable();
            $table->json('shopify_response')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
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
        Schema::dropIfExists('draft_orders');
    }
};
