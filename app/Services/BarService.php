<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Illuminate\Support\Str;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\DB;

class BarService
{
    public function __construct(private readonly ImportService $importService)
    {
    }

    public function openBar(Bar $bar, User $user, array $flags = []): bool
    {
        $this->importBaseData('glasses', resource_path('/data/base_glasses.yml'), $bar->id);
        $this->importBaseData('cocktail_methods', resource_path('/data/base_methods.yml'), $bar->id);
        $this->importBaseData('utensils', resource_path('/data/base_utensils.yml'), $bar->id);
        $this->importBaseData('ingredient_categories', resource_path('/data/base_ingredient_categories.yml'), $bar->id);

        $cocktails = Yaml::parseFile(resource_path('/data/popular_cocktails.yml'));
        foreach ($cocktails as $cocktail) {
            $cocktail['images'][0]['resource_path'] = sprintf('data/cocktails/%s', Str::slug($cocktail['name']) . '.jpg');
            $this->importService->importCocktailFromArray($cocktail, $user->id, $bar->id);
        }

        return true;
    }

    private function importBaseData(string $tableName, string $filepath, int $barId)
    {
        $importData = array_map(function (array $item) use ($barId) {
            $item['bar_id'] = $barId;

            return $item;
        }, Yaml::parseFile($filepath));

        DB::table($tableName)->insert($importData);
    }
}
