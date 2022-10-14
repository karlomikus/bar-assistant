<?php
declare(strict_types=1);

namespace Kami\Cocktail;

class UpdateSiteSearch
{
    public static function update($model)
    {
        /** @var \Laravel\Scout\Engines\MeiliSearchEngine */
        $engine = app(\Laravel\Scout\EngineManager::class)->engine();

        $engine->index('site_search_index')->addDocuments([
            $model->toSiteSearchArray()
        ], 'key');
    }
}
