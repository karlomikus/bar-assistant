<?php

use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add column and temporary make it nullable
        Schema::table('images', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users');
        });

        // Update ingredient image user
        Ingredient::all()->each(function ($ingredient) {
            $userId = $ingredient->user_id;

            $ingredient->images()->update(['user_id' => $userId]);
        });

        // Update cocktail image user
        Cocktail::all()->each(function ($cocktail) {
            $userId = $cocktail->user_id;

            $cocktail->images()->update(['user_id' => $userId]);
        });

        // Handle unassigned images
        $result = DB::table('users')->orderBy('id', 'asc')->first('id');
        DB::table('images')->whereNull('user_id')->update(['user_id' => $result->id]);

        // Make user not nullable
        Schema::table('images', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
