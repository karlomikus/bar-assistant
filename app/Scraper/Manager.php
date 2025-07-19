<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

use Throwable;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\Scraper\Sites\DefaultScraper;
use Kami\Cocktail\Exceptions\ScraperMissingException;

final class Manager
{
    /**
     * @var array<class-string<AbstractSite>>
     */
    private array $supportedSites = [
        \Kami\Cocktail\Scraper\Sites\TuxedoNo2::class,
        \Kami\Cocktail\Scraper\Sites\ImbibeMagazine::class,
        \Kami\Cocktail\Scraper\Sites\EricsCocktailGuide::class,
        \Kami\Cocktail\Scraper\Sites\HausAlpenz::class,
        \Kami\Cocktail\Scraper\Sites\DiffordsGuide::class,
        \Kami\Cocktail\Scraper\Sites\TheCocktailDB::class,
        \Kami\Cocktail\Scraper\Sites\CocktailParty::class,
        \Kami\Cocktail\Scraper\Sites\LiberAndCo::class,
        \Kami\Cocktail\Scraper\Sites\CocktailsDistilled::class,
        \Kami\Cocktail\Scraper\Sites\PunchDrink::class,
        \Kami\Cocktail\Scraper\Sites\SteveTheBartender::class,
        \Kami\Cocktail\Scraper\Sites\LiquorCom::class,
        \Kami\Cocktail\Scraper\Sites\MakeMeACocktail::class,
        \Kami\Cocktail\Scraper\Sites\KindredCocktails::class,
        \Kami\Cocktail\Scraper\Sites\CraftedPour::class,
        \Kami\Cocktail\Scraper\Sites\CocktailExplorer::class,
    ];

    public function __construct(private readonly string $url)
    {
    }

    private function matchFirst(): AbstractSite
    {
        foreach ($this->supportedSites as $siteClass) {
            foreach ($siteClass::getSupportedUrls() as $supportedHostname) {
                if (str_starts_with($this->url, $supportedHostname)) {
                    return resolve($siteClass, ['url' => $this->url]);
                }
            }
        }

        // Fallback to schema scraper
        try {
            return resolve(DefaultScraper::class, ['url' => $this->url]);
        } catch (Throwable $e) {
            Log::debug('Scraping failed: ' . $e->getMessage());
        }

        throw new ScraperMissingException('Scraper could not find any relevant data for the given site.');
    }

    public static function scrape(string $url): AbstractSite
    {
        return (new self($url))->matchFirst();
    }
}
