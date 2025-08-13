<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kami\Cocktail\Scraper\Sites\DefaultScraper;
use Kami\Cocktail\Exceptions\ScraperMissingException;
use Kevinrob\GuzzleCache\Storage\LaravelCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Spatie\Robots\Robots;

/**
 * TODO: Refactor this mess of a class:
 * Split content fetching, site matching, and scraping logic into separate classes.
 * Make this a factory class
 */
final class Manager
{
    const USER_AGENT = 'BarAssistantBot/1.0';

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

    public static function scrape(string $url, ?string $content = null): AbstractSite
    {
        return (new self($url))->matchFirst($content);
    }

    private function matchFirst(?string $content = null): AbstractSite
    {
        if ($content === null && !$this->scrapingAllowed()) {
            throw new ScraperMissingException(
                "This site does not allow scraping the given URL. Please check the site's robots.txt file for more information."
            );
        }

        $scraperClass = $this->matchSite();
        if ($content === null) {
            $content = $this->getSiteContent();
        }

        if (!str_starts_with($content, '<!DOCTYPE html>')) {
            $content = '<!DOCTYPE html>' . $content;
        }

        if (empty($content)) {
            throw new ScraperMissingException('Scraper could not find any relevant data for the given site.');
        }

        return resolve($scraperClass, ['url' => $this->url, 'content' => $content]);
    }

    private function matchSite(): string
    {
        foreach ($this->supportedSites as $siteClass) {
            foreach ($siteClass::getSupportedUrls() as $supportedHostname) {
                if (str_starts_with($this->url, $supportedHostname)) {
                    return $siteClass;
                }
            }
        }

        return DefaultScraper::class;
    }

    private function getSiteContent(): string
    {
        //$saf = mt_rand(531, 536) . mt_rand(0, 2);
        //$userAgent = "(X11; Linux x86_64) AppleWebKit/$saf (KHTML, like Gecko) Chrome/" . mt_rand(36, 40) . '.0.' . mt_rand(800, 899) . ".0 Mobile Safari/$saf";
        $cachingMiddleware = new CacheMiddleware(new GreedyCacheStrategy(new LaravelCacheStorage(Cache::store()), 60 * 15));
        $response = Http::withMiddleware($cachingMiddleware)
            ->withUserAgent(self::USER_AGENT)
            ->timeout(10)
            ->get($this->url);

        return $response->body();
    }

    private function scrapingAllowed(): bool
    {
        $robots = Robots::create(self::USER_AGENT);

        return $robots->mayIndex($this->url);
    }
}
