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
        Schema::create('shopify_webhook_idempotencies', function (Blueprint $table) {
            $table->id();
            $table->string('webhook_id')->unique();
            $table->string('topic')->nullable();
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shopify_webhook_idempotencies');
    }
};
