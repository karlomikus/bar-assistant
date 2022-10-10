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
            $table->text('description')->nullable();
            $table->string('source')->nullable();
            $table->text('garnish')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('parent_cocktail_id')->nullable()->constrained('cocktails');
            $table->timestamps();
        });

        Schema::create('cocktail_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained();
            $table->foreignId('cocktail_id')->constrained();
            $table->integer('amount');
            $table->string('units');
            $table->integer('sort')->default(0);
            $table->boolean('optional')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cocktail_ingredients');
        Schema::dropIfExists('cocktails');
    }
};
