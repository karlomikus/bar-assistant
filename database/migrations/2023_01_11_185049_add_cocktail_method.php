<?php

use Illuminate\Support\Facades\DB;
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
        Schema::create('cocktail_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('dilution_percentage');
        });

        DB::table('cocktail_methods')->insert([
            ['name' => 'Shake', 'dilution_percentage' => 25],
            ['name' => 'Stir', 'dilution_percentage' => 20],
            ['name' => 'Build', 'dilution_percentage' => 10],
            ['name' => 'Blend', 'dilution_percentage' => 25],
            ['name' => 'Muddle', 'dilution_percentage' => 10],
            ['name' => 'Layer', 'dilution_percentage' => 0],
        ]);

        Schema::table('cocktails', function (Blueprint $table) {
            $table->foreignId('cocktail_method_id')->nullable()->constrained('cocktail_methods');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cocktails', function (Blueprint $table) {
            $table->dropColumn('cocktail_method_id');
        });

        Schema::dropIfExists('cocktail_methods');
    }
};
