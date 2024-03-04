<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\TestCase;
use Kami\Cocktail\Scraper\Manager;

class MakeMeACocktailScraperTest extends TestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://makemeacocktail.com/cocktail/6781/negroni/');
        $result = $scraper->toArray();

        $instructions = "1. Add all the ingredients directly into a lowball glass with ice and stir\n2. Garnish with an orange slice and serve";

        $this->assertStringContainsString('Negroni', $result['name']);
        $this->assertNotEmpty($result['description']);
        $this->assertSame('https://makemeacocktail.com/cocktail/6781/negroni/', $result['source']);
        $this->assertSame(null, $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame(null, $result['garnish']);
        $this->assertSame(['cocktail'], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertNotEmpty($result['images'][0]['url']);
        $this->assertSame('Make Me a Cocktail', $result['images'][0]['copyright']);

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
        $this->assertSame('Red vermouth', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);
    }
}
