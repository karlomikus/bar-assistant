<?php
declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpClient\CachingHttpClient;

class Scraper
{
    private $crawler;

    public function __construct(
        private readonly string $url,
    )
    {
        $store = new Store(storage_path('http_cache/'));
        $client = HttpClient::create();
        $client = new CachingHttpClient($client, $store);
        $browser = new HttpBrowser($client);

        $browser->request('GET', $url);
        $this->crawler = new Crawler($browser->getResponse()->getContent());
    }

    public function name()
    {
        return $this->crawler->filter('.recipe__header-title')->first()->text();
    }

    public function description()
    {
        return $this->crawler->filter('.recipe__header-subtitle')->first()->text();
    }

    public function source()
    {
        return $this->url;
    }

    public function instructions()
    {
        $result = '';

        $step = 1;
        $this->crawler->filter('.recipe__recipe ol')->first()->filter('li')->each(function ($node) use (&$result, &$step) {
            $result .= $step . ". " . $node->text() . "\n";
            $step++;
        });

        return $result;
    }

    public function tags()
    {
        return null;
    }

    public function glass()
    {
        $glassTag = $this->crawler->filter('.recipe__header-titles-and-icons .recipe__tag-icons a')->last()->attr('href');

        return str_replace(
            '-',
            ' ',
            str_replace('/tags/', '', $glassTag)
        );
    }

    public function ingredients()
    {
        $result = [];

        $this->crawler->filter('.recipe__recipe ul')->first()->filter('li')->each(function ($node) use (&$result) {
            $isGarnish = $node->filter('.amount .unit')->count() === 0;

            if ($isGarnish) {
                return;
            }

            $unit = $node->filter('.amount .unit')->text();
            $amount = iconv('','US//TRANSLIT', str_replace($unit, '', $node->filter('.amount')->text()));

            if ($unit === 'oz') {
                $numbers = explode('/', $amount);

                $denominator = $numbers[1] ?? 1;
                $amount = ((int) $numbers[0] / $denominator) * 30;
                $unit = 'ml';
            }

            $result[] = [
                'amount' => $amount,
                'unit' => $unit,
                'name' => $node->filter('.ingredient a')->first()->text(),
                'optional' => false,
            ];
        });

        return $result;
    }

    public function garnish()
    {
        return null;
    }

    public function toArray()
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'source' => $this->source(),
            'glass' => $this->glass(),
            'instructions' => $this->instructions(),
            'garnish' => $this->garnish(),
            'tags' => $this->tags(),
            'ingredients' => $this->ingredients(),
        ];
    }
}
