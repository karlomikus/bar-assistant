<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Kami\Cocktail\Search\SearchActionsAdapter;
use Kami\Cocktail\Search\SearchActionsContract;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // TODO:
        // Moving to new schema will take too much time
        // Easiest way is to export/backup all old data
        // Drop all current data
        // Offer import from previous version when creating a new bar
        // Maybe import from .sqlite file?

        // Remove unused columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('search_api_key');
        });

        Schema::table('cocktails', function (Blueprint $table) {
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        });

        Schema::table('images', function (Blueprint $table) {
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        });

        Schema::table('glasses', function (Blueprint $table) {
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        });

        Schema::table('ingredient_categories', function (Blueprint $table) {
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        });

        DB::table('utensils')->truncate();
        Schema::table('utensils', function (Blueprint $table) {
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        });

        DB::table('cocktail_methods')->truncate();
        Schema::table('cocktail_methods', function (Blueprint $table) {
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->foreignId('bar_membership_id')->constrained()->onDelete('cascade');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->foreignId('bar_membership_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not planned yet...
    }
};
