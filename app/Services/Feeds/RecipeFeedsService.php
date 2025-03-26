<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Feeds;

use Throwable;
use Laminas\Feed\Reader\Reader;
use Illuminate\Support\Facades\Log;
use Laminas\Feed\Reader\Http\ClientInterface;

final class RecipeFeedsService
{
    /** @var string[] */
    private array $feeds = [
        'https://punchdrink.com/recipe-archives/feed/',
        'https://feeds.feedburner.com/punchdrink',
        'https://abarabove.com/feed/',
        'https://imbibemagazine.com/category/recipes/cocktails-spirits-recipes/feed/',
        'https://imbibemagazine.com/category/recipes/alcohol-free-recipes/feed/',
        'https://www.theeducatedbarfly.com/category/recipes/cocktails/feed/',
        // 'https://australianbartender.com.au/feed/',
        // 'https://chilledmagazine.com/feed/',
        // 'https://imbibemagazine.com/feed/',
    ];

    public function __construct(private readonly ClientInterface $client)
    {
    }

    /**
     * @return array<FeedsRecipe>
     */
    public function fetch(): array
    {
        if ((bool) config('bar-assistant.enable_feeds') === false) {
            return [];
        }

        Reader::setHttpClient($this->client);
        $recipes = [];

        foreach ($this->feeds as $feedUrl) {
            try {
                $feed = Reader::import($feedUrl);
            } catch (Throwable $e) {
                Log::error("Failed to fetch feed from $feedUrl: {$e->getMessage()}");
                continue;
            }

            foreach ($feed as $entry) {
                $recipes[] = FeedsRecipe::fromLaminasEntry($entry, $feed->getTitle());
            }
        }

        // Sort the recipes by date
        usort($recipes, function (FeedsRecipe $a, FeedsRecipe $b) {
            return $b->dateModified <=> $a->dateModified;
        });

        return $recipes;
    }
}
