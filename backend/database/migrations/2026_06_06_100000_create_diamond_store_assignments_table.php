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
        Schema::create('diamond_store_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diamond_id')->constrained('diamonds')->onDelete('cascade');
            $table->foreignId('shopify_store_id')->constrained('shopify_stores')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->unique(['diamond_id', 'shopify_store_id'], 'uq_diamond_store_assignment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('diamond_store_assignments');
    }
};
