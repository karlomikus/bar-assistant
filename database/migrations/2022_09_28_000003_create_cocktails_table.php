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
        Schema::create('cocktails', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('instructions');
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });

        Schema::create('cocktail_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained();
            $table->foreignId('cocktail_id')->constrained();
            $table->integer('amount');
            $table->string('units');
            $table->integer('sort')->default(0);
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
        Schema::dropIfExists('cocktails');
        Schema::dropIfExists('cocktail_ingredients');
    }
};
