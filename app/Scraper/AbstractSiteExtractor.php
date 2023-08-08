<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpClient\CachingHttpClient;

abstract class AbstractSiteExtractor implements SiteExtractorContract
{
    protected readonly Crawler $crawler;

    public function __construct(
        protected readonly string $url,
    ) {
        $store = new Store(storage_path('http_cache/'));
        $client = HttpClient::create();
        $client = new CachingHttpClient($client, $store);
        $browser = new HttpBrowser($client);

        $browser->request('GET', $url);

        $this->crawler = new Crawler($browser->getResponse()->getContent());
    }

    /**
     * Array with a list of support sites. All sites must be defined
     * with protocol (ex: https://) and end without slash
     *
     * @return array<string>
     */
    abstract public static function getSupportedUrls(): array;

    /**
     * Cocktail name
     *
     * @return string
     */
    abstract public function name(): string;

    /**
     * Cocktail description, can support markdown
     *
     * @return null|string
     */
    public function description(): ?string
    {
        return null;
    }

    /**
     * Cocktail source URL
     *
     * @return null|string
     */
    public function source(): ?string
    {
        return null;
    }

    /**
     * Cocktail preparation instructions, can support markdown
     *
     * @return null|string
     */
    abstract public function instructions(): ?string;

    /**
     * Cocktail tags
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [];
    }

    /**
     * Cocktail serving glass
     *
     * @return null|string
     */
    public function glass(): ?string
    {
        return null;
    }

    /**
     * Array containing cocktail ingredients
     *
     * @return array<int, array{"amount": float|int, "units": string, "name": string, "optional": boolean}>
     */
    public function ingredients(): array
    {
        return [];
    }

    /**
     * Cocktail garnish, can support markdown
     *
     * @return null|string
     */
    public function garnish(): ?string
    {
        return null;
    }

    /**
     * Array containing image information
     *
     * @return null|array{"url": string|null, "copyright": string|null}
     */
    public function image(): ?array
    {
        return null;
    }

    /**
     * Cocktail method (shake, stir...)
     *
     * @return null|string
     */
    public function method(): ?string
    {
        return null;
    }

    /**
     * Cocktail information as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => preg_replace("/\s+/u", " ", $this->name()),
            'description' => $this->description(),
            'source' => $this->source(),
            'glass' => $this->glass(),
            'instructions' => $this->instructions(),
            'garnish' => $this->garnish(),
            'tags' => $this->tags(),
            'method' => $this->method(),
            'images' => [
                $this->image()
            ],
            'ingredients' => $this->ingredients(),
        ];
    }
}
