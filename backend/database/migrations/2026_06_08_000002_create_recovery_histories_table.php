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
        Schema::create('shopify_recovery_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('stores_scanned')->default(0);
            $table->integer('products_checked')->default(0);
            $table->integer('issues_fixed')->default(0);
            $table->integer('drafted_count')->default(0);
            $table->integer('republished_count')->default(0);
            $table->string('status')->default('pending'); // pending, completed, failed
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
        Schema::dropIfExists('shopify_recovery_histories');
    }
};
