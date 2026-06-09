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
        Schema::create('jeweleries', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable();
            $table->string('name')->nullable();
            $table->string('type')->nullable(); // Ring, Bracelet, Earings, Necklace, Watch, Pendent
            $table->decimal('price', 12, 2)->nullable();
            $table->string('image_url')->nullable();
            $table->string('location')->nullable();
            $table->string('created_by')->nullable();


            // The JSON specifications column (combines the remaining properties)
            $table->json('specifications')->nullable();

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
        Schema::dropIfExists('jeweleries');
    }
};
