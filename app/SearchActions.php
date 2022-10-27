<?php
declare(strict_types=1);

namespace Kami\Cocktail;

class SearchActions
{
    public static function updateCocktailIndex()
    {
        /** @var \Laravel\Scout\Engines\MeiliSearchEngine */
        $engine = app(\Laravel\Scout\EngineManager::class)->engine();

        $engine->index('cocktails')->updateSettings([
            'filterableAttributes' => ['tags'],
            'sortableAttributes' => ['id', 'name', 'date']
        ]);
    }

    public static function update($model)
    {
        /** @var \Laravel\Scout\Engines\MeiliSearchEngine */
        $engine = app(\Laravel\Scout\EngineManager::class)->engine();

        $engine->index('site_search_index')->addDocuments([
            $model->toSiteSearchArray()
        ], 'key');
    }

    public static function delete($model)
    {
        /** @var \Laravel\Scout\Engines\MeiliSearchEngine */
        $engine = app(\Laravel\Scout\EngineManager::class)->engine();

        $engine->index('site_search_index')->deleteDocument($model->toSiteSearchArray()['key']);
    }

    public static function flushSearchIndex()
    {
        /** @var \Laravel\Scout\Engines\MeiliSearchEngine */
        $engine = app(\Laravel\Scout\EngineManager::class)->engine();

        $engine->index('site_search_index')->delete();
    }
}
