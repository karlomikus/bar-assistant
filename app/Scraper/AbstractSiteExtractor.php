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

    abstract public static function getSupportedUrls(): array;

    abstract public function name(): string;

    abstract public function description(): ?string;

    abstract public function source(): ?string;

    abstract public function instructions(): ?string;

    abstract public function tags(): array;

    abstract public function glass(): ?string;

    abstract public function ingredients(): array;

    abstract public function garnish(): ?string;

    abstract public function image(): ?array;

    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'source' => $this->source(),
            'glass' => $this->glass(),
            'instructions' => $this->instructions(),
            'garnish' => $this->garnish(),
            'tags' => $this->tags(),
            'image' => $this->image(),
            'ingredients' => $this->ingredients(),
        ];
    }
}
