<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\ScraperTestCase;
use Kami\Cocktail\Scraper\Manager;

class HausAlpenzScraperTest extends ScraperTestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://alpenz.com/recipe-ikfCvvprSL.html');
        $result = $scraper->toArray();

        $instructions = "1. Strain into a coupe.";

        $this->assertSame('Negroni Rosa', $result['name']);
        $this->assertSame(null, $result['description']);
        $this->assertSame('https://alpenz.com/recipe-ikfCvvprSL.html', $result['source']);
        $this->assertSame('Lowball', $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame('Garnish with orange peel.', $result['garnish']);
        $this->assertSame([], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertSame('https://alpenz-recipes-managed.s3.amazonaws.com/ikfCvvprSL.drink-photo.jpg', $result['images'][0]['url']);
        $this->assertSame('Haus Alpenz', $result['images'][0]['copyright']);

        $this->assertSame(30.0, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('Hayman\'s London Dry Gin', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(30.0, $result['ingredients'][1]['amount']);
        $this->assertSame('ml', $result['ingredients'][1]['units']);
        $this->assertSame('Cocchi Americano Rosa', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);

        $this->assertSame(30.0, $result['ingredients'][2]['amount']);
        $this->assertSame('ml', $result['ingredients'][2]['units']);
        $this->assertSame('Aperitivo Cappelletti', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);
    }
}
