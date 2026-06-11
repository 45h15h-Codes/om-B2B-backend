<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // SQLite (used in automated tests) does not support MySQL MODIFY syntax.
        // We only modify the column type and constraint for MySQL connections.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE orders MODIFY customer_id BIGINT UNSIGNED NULL');

            Schema::table('orders', function (Blueprint $table) {
                // Add the foreign key constraint referencing customers table
                $table->foreign('customer_id')
                      ->references('id')
                      ->on('customers')
                      ->onDelete('set null');
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
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('orders', function (Blueprint $table) {
                // Drop foreign key constraint
                $table->dropForeign(['customer_id']);
            });

            // Revert column back to VARCHAR(255) using raw SQL
            DB::statement('ALTER TABLE orders MODIFY customer_id VARCHAR(255) NULL');
        }
    }
};
