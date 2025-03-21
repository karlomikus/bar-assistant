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
        Schema::table('cocktail_tag', function (Blueprint $table) {
            $table->index('cocktail_id', 'cocktail_tag_cocktail_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cocktail_tag', function (Blueprint $table) {
            $table->dropIndex('cocktail_tag_cocktail_id_idx');
        });
    }
};
