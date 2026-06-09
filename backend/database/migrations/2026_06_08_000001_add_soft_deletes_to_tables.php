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
        Schema::table('notifications', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('import_histories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('shopify_webhook_logs', function (Blueprint $table) {
            $table->integer('retry_count')->default(0)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_webhook_logs', function (Blueprint $table) {
            $table->dropColumn('retry_count');
        });

        Schema::table('import_histories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
