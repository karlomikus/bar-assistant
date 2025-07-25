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
        Schema::table('cocktails', function (Blueprint $table) {
            $table->foreignId('parent_cocktail_id')->nullable()->constrained('cocktails')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cocktails', function (Blueprint $table) {
            $table->dropColumn('parent_cocktail_id');
        });
    }
};
