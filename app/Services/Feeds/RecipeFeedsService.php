<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Feeds;

use Throwable;
use Laminas\Feed\Reader\Reader;
use Illuminate\Support\Facades\Log;
use Laminas\Feed\Reader\Http\ClientInterface;

final class RecipeFeedsService
{
    /** @var array<array{url: string, supportsRecipeImport: bool}> */
    private array $feeds = [
        ['url' => 'https://punchdrink.com/recipe-archives/feed/', 'supportsRecipeImport' => true],
        ['url' => 'https://feeds.feedburner.com/punchdrink', 'supportsRecipeImport' => false],
        ['url' => 'https://abarabove.com/feed/', 'supportsRecipeImport' => false],
        ['url' => 'https://imbibemagazine.com/category/recipes/cocktails-spirits-recipes/feed/', 'supportsRecipeImport' => true],
        ['url' => 'https://imbibemagazine.com/category/recipes/alcohol-free-recipes/feed/', 'supportsRecipeImport' => true],
        ['url' => 'https://www.theeducatedbarfly.com/category/recipes/cocktails/feed/', 'supportsRecipeImport' => true],
        ['url' => 'https://feeds-api.dotdashmeredith.com/v1/rss/google/7d333f07-9a05-4a69-aae7-7f2daacd7ebc', 'supportsRecipeImport' => false],
        // ['url' => 'https://cocktailvirgin.blogspot.com/feeds/posts/default', 'supportsRecipeImport' => true],
        // ['url' => 'https://www.reddit.com/r/cocktails/top/.rss?sort=top&t=week', 'supportsRecipeImport' => false],
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

        foreach ($this->feeds as $feed) {
            try {
                $feedUrl = $feed['url'];
                $supportsRecipeImport = $feed['supportsRecipeImport'];
                $feedData = Reader::import($feedUrl);
            } catch (Throwable $e) {
                Log::error("Failed to fetch feed from {$feed['url']}: {$e->getMessage()}");
                continue;
            }

            foreach ($feedData as $entry) {
                $recipes[] = FeedsRecipe::fromLaminasEntry($entry, $feedData->getTitle(), $supportsRecipeImport);
            }
        }

        // Sort the recipes by date
        usort($recipes, fn (FeedsRecipe $a, FeedsRecipe $b) => $b->dateModified <=> $a->dateModified);

        return $recipes;
    }
}
