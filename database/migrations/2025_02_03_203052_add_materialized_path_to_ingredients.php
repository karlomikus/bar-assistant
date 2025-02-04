<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('materialized_path')->nullable()->index('ing_path_index');
        });

        DB::select("UPDATE ingredients SET materialized_path = parent_ingredient_id || '/' WHERE parent_ingredient_id IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropIndex('ing_path_index');
            $table->dropColumn('materialized_path');
        });
    }
};
