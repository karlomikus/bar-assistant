<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

use Throwable;
use Kami\Cocktail\Exceptions\ScrapeException;
use Kami\Cocktail\Scraper\Sites\DefaultScraper;

final class Manager
{
    private array $supportedSites = [
        \Kami\Cocktail\Scraper\Sites\ACoupleCooks::class,
        \Kami\Cocktail\Scraper\Sites\TuxedoNo2::class,
        \Kami\Cocktail\Scraper\Sites\Imbibe::class,
        \Kami\Cocktail\Scraper\Sites\EricsCocktailGuide::class,
        \Kami\Cocktail\Scraper\Sites\HausAlpenz::class,
        \Kami\Cocktail\Scraper\Sites\TheDrinkBlog::class,
    ];

    public function __construct(private readonly string $url)
    {
    }

    private function matchFirst(): AbstractSiteExtractor
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
        }

        throw new ScrapeException('Scraper could not find any relevant data for the given site.');
    }

    public static function scrape(string $url): AbstractSiteExtractor
    {
        return (new self($url))->matchFirst();
    }
}
