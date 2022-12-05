<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

use Kami\Cocktail\Exceptions\ScrapeException;

final class Manager
{
    private $supportedSites = [
        \Kami\Cocktail\Scraper\Sites\ACoupleCooks::class,
        \Kami\Cocktail\Scraper\Sites\TuxedoNo2::class,
        \Kami\Cocktail\Scraper\Sites\Imbibe::class,
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

        throw new ScrapeException('Scraper not supported for given site.');
    }

    public static function scrape(string $url): AbstractSiteExtractor
    {
        return (new self($url))->matchFirst();
    }
}
