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
        Schema::table('shopify_inventory_audits', function (Blueprint $table) {
            $table->foreignId('jewelry_id')->nullable()->after('diamond_id')->constrained('jeweleries')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_inventory_audits', function (Blueprint $table) {
            $table->dropForeign(['jewelry_id']);
            $table->dropColumn('jewelry_id');
        });
    }
};
