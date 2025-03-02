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
        Schema::table('ingredients', function (Blueprint $table) {
            $table->float('sugar_g_per_ml')->nullable();
            $table->float('acidity')->nullable();
            $table->string('distillery')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('sugar_g_per_ml');
            $table->dropColumn('acidity');
            $table->dropColumn('distillery');
        });
    }
};
