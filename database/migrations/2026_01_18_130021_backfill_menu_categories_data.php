<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all unique category names per menu from both tables, ordered alphabetically
        $categoriesFromCocktails = DB::table('menu_cocktails')
            ->select('menu_id', 'category_name')
            ->groupBy('menu_id', 'category_name');

        $categoriesFromIngredients = DB::table('menu_ingredients')
            ->select('menu_id', 'category_name')
            ->groupBy('menu_id', 'category_name');

        $categories = $categoriesFromCocktails
            ->union($categoriesFromIngredients)
            ->orderBy('menu_id')
            ->orderBy('category_name') // Alphabetical order
            ->get();

        // Group by menu_id and create menu_categories records with sort index
        $categoriesByMenu = $categories->groupBy('menu_id');

        foreach ($categoriesByMenu as $menuId => $menuCategories) {
            $sortIndex = 0;
            $categoryMap = [];

            foreach ($menuCategories as $category) {
                $categoryId = DB::table('menu_categories')->insertGetId([
                    'menu_id' => $menuId,
                    'name' => $category->category_name,
                    'sort' => $sortIndex++,
                ]);

                $categoryMap[$category->category_name] = $categoryId;
            }

            // Update menu_cocktails with category IDs for this menu
            foreach ($categoryMap as $categoryName => $categoryId) {
                DB::table('menu_cocktails')
                    ->where('menu_id', $menuId)
                    ->where('category_name', $categoryName)
                    ->update(['menu_category_id' => $categoryId]);
            }

            // Update menu_ingredients with category IDs for this menu
            foreach ($categoryMap as $categoryName => $categoryId) {
                DB::table('menu_ingredients')
                    ->where('menu_id', $menuId)
                    ->where('category_name', $categoryName)
                    ->update(['menu_category_id' => $categoryId]);
            }
        }

        Schema::table('menu_ingredients', function (Blueprint $table) {
            $table->dropColumn('category_name');
            $table->dropConstrainedForeignId('menu_id');
        });
        Schema::table('menu_cocktails', function (Blueprint $table) {
            $table->dropColumn('category_name');
            $table->dropConstrainedForeignId('menu_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('menu_categories')->delete();
    }
};
