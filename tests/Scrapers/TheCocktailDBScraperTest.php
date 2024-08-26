<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\ScraperTestCase;
use Kami\Cocktail\Scraper\Manager;

class TheCocktailDBScraperTest extends ScraperTestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://www.thecocktaildb.com/drink/11003-Negroni');
        $result = $scraper->toArray();

        $instructions = "Stir into glass over ice, garnish and serve.";

        $this->assertSame('Negroni', $result['name']);
        $this->assertSame(null, $result['description']);
        $this->assertSame('https://www.thecocktaildb.com/drink/11003-Negroni', $result['source']);
        $this->assertSame('Old-fashioned glass', $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame(null, $result['garnish']);
        $this->assertSame(['IBA', 'Classic'], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertSame('https://www.thecocktaildb.com/images/media/drink/qgdu971561574065.jpg', $result['images'][0]['url']);
        $this->assertSame('TheCocktailDB.com', $result['images'][0]['copyright']);

        $this->assertSame(30.0, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('Gin', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(30.0, $result['ingredients'][1]['amount']);
        $this->assertSame('ml', $result['ingredients'][1]['units']);
        $this->assertSame('Campari', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);

        $this->assertSame(30.0, $result['ingredients'][2]['amount']);
        $this->assertSame('ml', $result['ingredients'][2]['units']);
        $this->assertSame('Sweet Vermouth', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);
    }
}
