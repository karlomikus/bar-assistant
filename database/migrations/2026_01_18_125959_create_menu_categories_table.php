<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create menu_categories table
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort')->default(0);

            // Ensure category names are unique per menu
            $table->unique(['menu_id', 'name']);
            $table->index('menu_id');
        });

        // Add menu_category_id to menu_cocktails
        Schema::table('menu_cocktails', function (Blueprint $table) {
            $table->foreignId('menu_category_id')
                ->nullable()
                ->after('id')
                ->constrained('menu_categories')
                ->cascadeOnDelete();

            $table->index('menu_category_id');
        });

        // Add menu_category_id to menu_ingredients
        Schema::table('menu_ingredients', function (Blueprint $table) {
            $table->foreignId('menu_category_id')
                ->nullable()
                ->after('id')
                ->constrained('menu_categories')
                ->cascadeOnDelete();

            $table->index('menu_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key columns from menu_ingredients
        Schema::table('menu_ingredients', function (Blueprint $table) {
            $table->dropForeign(['menu_category_id']);
            $table->dropIndex(['menu_category_id']);
            $table->dropColumn('menu_category_id');
        });

        // Remove foreign key columns from menu_cocktails
        Schema::table('menu_cocktails', function (Blueprint $table) {
            $table->dropForeign(['menu_category_id']);
            $table->dropIndex(['menu_category_id']);
            $table->dropColumn('menu_category_id');
        });

        // Drop menu_categories table
        Schema::dropIfExists('menu_categories');
    }
};
