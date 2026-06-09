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
        Schema::create('sync_jobs_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopify_store_id')->constrained('shopify_stores')->onDelete('cascade');
            $table->string('job_type'); // e.g. orders_sync, products_sync, recovery_sync
            $table->string('status')->default('running'); // running, completed, failed
            $table->integer('records_processed')->default(0);
            $table->text('errors')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['shopify_store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sync_jobs_history');
    }
};
