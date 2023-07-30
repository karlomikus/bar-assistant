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
        DB::table('cocktails')->orderBy('id')->lazy()->each(function ($cocktail) {
            $ingredients = DB::table('cocktail_ingredients')->where('cocktail_id', $cocktail->id)->get();
            $i = 1;
            foreach ($ingredients as $ci) {
                DB::table('cocktail_ingredients')->where('id', $ci->id)->orderBy('id')->update(['sort' => $i]);
                $i++;
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
