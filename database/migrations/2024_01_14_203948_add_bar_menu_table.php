<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bars', function (Blueprint $table) {
            $table->string('slug')->nullable();

            $table->unique('slug');
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bar_id');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique('bar_id');
            $table->index('bar_id', 'm_bar_id_index');
        });

        Schema::create('menu_cocktails', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->integer('sort')->default(0);
            $table->foreignId('menu_id');
            $table->foreignId('cocktail_id');
            $table->integer('price')->default(0);
            $table->string('currency')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bars', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::dropIfExists('menu_cocktails');
        Schema::dropIfExists('menus');
    }
};
