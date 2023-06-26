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
    abstract public function description(): ?string;

    /**
     * Cocktail source URL
     *
     * @return null|string
     */
    abstract public function source(): ?string;

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
    abstract public function tags(): array;

    /**
     * Cocktail serving glass
     *
     * @return null|string
     */
    abstract public function glass(): ?string;

    /**
     * Array containing cocktail ingredients
     *
     * @return array<int, array{"amount": float|int, "units": string, "name": string, "optional": boolean}>
     */
    abstract public function ingredients(): array;

    /**
     * Cocktail garnish, can support markdown
     *
     * @return null|string
     */
    abstract public function garnish(): ?string;

    /**
     * Array containing image information
     *
     * @return null|array{"url": string|null, "copyright": string|null}
     */
    abstract public function image(): ?array;

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
            'images' => [
                $this->image()
            ],
            'ingredients' => $this->ingredients(),
        ];
    }
}
