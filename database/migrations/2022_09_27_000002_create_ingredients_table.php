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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('strength')->default(0.0);
            $table->string('description')->nullable();
            $table->text('origin')->nullable();
            $table->text('history')->nullable();
            $table->string('color')->nullable();
            $table->foreignId('ingredient_category_id')->constrained();
            $table->foreignId('parent_ingredient_id')->nullable()->constrained('ingredients')->onDelete('cascade');
            $table->text('aliases')->nullable();
            $table->foreignId('user_id')->constrained('users');
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
        Schema::dropIfExists('ingredients');
    }
};
