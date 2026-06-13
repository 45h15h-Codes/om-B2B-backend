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
        Schema::table('diamonds', function (Blueprint $table) {
            $table->json('images')->nullable();
            $table->json('videos')->nullable();
        });

        Schema::table('jeweleries', function (Blueprint $table) {
            $table->json('images')->nullable();
            $table->json('videos')->nullable();
        });
    }
 
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('diamonds', function (Blueprint $table) {
            $table->dropColumn(['images', 'videos']);
        });

        Schema::table('jeweleries', function (Blueprint $table) {
            $table->dropColumn(['images', 'videos']);
        });
    }
};

