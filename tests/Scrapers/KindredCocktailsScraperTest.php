<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\ScraperTestCase;
use Kami\Cocktail\Scraper\Manager;

class KindredCocktailsScraperTest extends ScraperTestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://kindredcocktails.com/cocktail/negroni');
        $result = $scraper->toArray();

        $this->assertSame('Negroni', $result['name']);
        $this->assertNull($result['description']);
        $this->assertSame('https://kindredcocktails.com/cocktail/negroni', $result['source']);
        $this->assertNull($result['glass']);
        $this->assertSame('Stir, strain, rocks, lowball. Garnish with orange twist or flamed orange peel.', $result['instructions']);
        $this->assertSame('Orange peel', $result['garnish']);
        $this->assertSame([], $result['tags']);
        $this->assertNull($result['method']);
        $this->assertSame('https://kindredcocktails.com/sites/kindredcocktails.com/files/styles/large/public/pictures/cocktail/negroni.jpg?itok=0MDOHJ3Y', $result['images'][0]['url']);
        $this->assertSame('2009, Creative Commons, Geoff Peters, Vancouver, BC', $result['images'][0]['copyright']);

        $this->assertCount(3, $result['ingredients']);

        $this->assertSame(30.0, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('Gin', $result['ingredients'][0]['name']);
        $this->assertSame('1 oz Gin', $result['ingredients'][0]['source']);

        $this->assertSame(30.0, $result['ingredients'][1]['amount']);
        $this->assertSame('ml', $result['ingredients'][1]['units']);
        $this->assertSame('Campari', $result['ingredients'][1]['name']);
        $this->assertSame('1 oz Campari', $result['ingredients'][1]['source']);

        $this->assertSame(30.0, $result['ingredients'][2]['amount']);
        $this->assertSame('ml', $result['ingredients'][2]['units']);
        $this->assertSame('Sweet vermouth', $result['ingredients'][2]['name']);
        $this->assertSame('1 oz Sweet vermouth', $result['ingredients'][2]['source']);
    }
}
