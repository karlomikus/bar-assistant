<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('menu_cocktails', function (Blueprint $table) {
            $table->boolean('is_bar_inventory_aware')->default(false);
        });
        Schema::table('menu_ingredients', function (Blueprint $table) {
            $table->boolean('is_bar_inventory_aware')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_cocktails', function (Blueprint $table) {
            $table->dropColumn('is_bar_inventory_aware');
        });
        Schema::table('menu_ingredients', function (Blueprint $table) {
            $table->dropColumn('is_bar_inventory_aware');
        });
    }
};
