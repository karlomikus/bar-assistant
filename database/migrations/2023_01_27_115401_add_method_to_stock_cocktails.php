<?php

use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
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
        // Lets skip this if we are starting from scratch
        $cocktailsWithoutMethod = DB::table('cocktails')->whereNull('cocktail_method_id')->count();
        if ($cocktailsWithoutMethod === 0) {
            return;
        }

        $sources = [
            Yaml::parseFile(resource_path('/data/iba_cocktails_v0.1.0.yml')),
            Yaml::parseFile(resource_path('/data/popular_cocktails.yml')),
        ];

        $dbMethods = DB::table('cocktail_methods')->select(['name', 'id'])->get();

        foreach ($sources as $source) {
            foreach ($source as $sCocktail) {
                $methodId = $dbMethods->filter(fn ($item) => $item->name == $sCocktail['method'])->first()->id ?? null;

                DB::table('cocktails')->where('name', $sCocktail['name'])->whereNull('cocktail_method_id')->update([
                    'cocktail_method_id' => $methodId,
                ]);
            }
        }

        Artisan::call('bar:refresh-search');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
