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
        Schema::create('diamonds', function (Blueprint $table) {
            $table->id();

            // General Information (Physical database columns)
            $table->string('stock_no')->nullable();
            $table->decimal('asking_price', 12, 2)->nullable();
            $table->string('asking_price_unit')->nullable(); // CT or OM %
            $table->decimal('cash_price', 12, 2)->nullable();
            $table->string('cash_price_unit')->nullable(); // CT or OM %
            $table->string('availability')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();

            // Key Report Information
            $table->string('shape')->nullable();
            $table->decimal('size', 8, 3)->nullable();
            $table->string('color')->nullable();
            $table->string('clarity')->nullable();
            
            // Primary toggles & indices
            $table->boolean('show_on_OM')->default(true);
            $table->boolean('is_matched_pair')->default(false);
            $table->boolean('is_parcel')->default(false);
            $table->integer('number_of_diamonds')->nullable();

            // The JSON array specifications column (combines 50+ other properties)
            $table->json('specifications')->nullable();

            // System Fields
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected
            $table->string('created_by')->default('Normal Admin');

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
        Schema::dropIfExists('diamonds');
    }
};

