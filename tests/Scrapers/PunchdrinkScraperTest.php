<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\ScraperTestCase;
use Kami\Cocktail\Scraper\Manager;

class PunchdrinkScraperTest extends ScraperTestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://punchdrink.com/recipes/milano-torino/');
        $result = $scraper->toArray();

        $this->assertSame('Milano-Torino', $result['name']);
        $this->assertSame('This simple, two-ingredient cocktail recipe represents the bittersweet blueprint for aperitivo drinks, like the Americano and the Negroni.', $result['description']);
        $this->assertSame('https://punchdrink.com/recipes/milano-torino/', $result['source']);
        $this->assertSame(null, $result['glass']);
        $this->assertSame("1. Build the drink in an Old-Fashioned glass over ice.\n2. Stir and garnish with an orange slice.", $result['instructions']);
        $this->assertSame('orange slice', $result['garnish']);
        $this->assertSame(['Bitter'], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertNotEmpty($result['images'][0]['url']);
        $this->assertSame('Punch Staff | Lizzie Munro', $result['images'][0]['copyright']);

        $this->assertSame(30.0, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('Campari', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(30.0, $result['ingredients'][1]['amount']);
        $this->assertSame('ml', $result['ingredients'][1]['units']);
        $this->assertSame('Vermouth di Torino', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);
    }
}
