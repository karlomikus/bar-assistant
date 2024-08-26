<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\ScraperTestCase;
use Kami\Cocktail\Scraper\Manager;

class EricsCocktailGuideScraperTest extends ScraperTestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://www.ericscocktailguide.com/recipes/negroni');
        $result = $scraper->toArray();

        $instructions = "Pour ingredients into ice-filled rocks glass and stir. Garnish with orange twist.";

        $this->assertSame('Negroni', $result['name']);
        $this->assertSame(null, $result['description']);
        $this->assertSame('https://www.ericscocktailguide.com/recipes/negroni', $result['source']);
        $this->assertSame(null, $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame(null, $result['garnish']);
        $this->assertSame([], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertSame('https://www.ericscocktailguide.com/images/cocktails/negroni.jpg', $result['images'][0]['url']);
        $this->assertSame('Eric\'s Cocktail Guide', $result['images'][0]['copyright']);

        $this->assertSame(30.0, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('Gin', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(30.0, $result['ingredients'][1]['amount']);
        $this->assertSame('ml', $result['ingredients'][1]['units']);
        $this->assertSame('Sweet vermouth', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);

        $this->assertSame(30.0, $result['ingredients'][2]['amount']);
        $this->assertSame('ml', $result['ingredients'][2]['units']);
        $this->assertSame('Campari', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);
    }
}
