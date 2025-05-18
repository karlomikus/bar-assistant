<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('complex_ingredients', function (Blueprint $table) {
            $table->index('main_ingredient_id', 'ci_main_ingredient_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complex_ingredients', function (Blueprint $table) {
            $table->dropIndex('ci_main_ingredient_idx');
        });
    }
};
