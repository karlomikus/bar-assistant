<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Throwable;
use Tests\ScraperTestCase;
use Kami\Cocktail\Scraper\Manager;

class CocktailsDistilledScraperTest extends ScraperTestCase
{
    public function testScrape(): void
    {
        try {
            $scraper = Manager::scrape('https://cocktailsdistilled.com/recipe/rakia-negroni/');
        } catch (Throwable $e) {
            $this->markTestSkipped('Scraping failed: ' . $e->getMessage());
        }

        $result = $scraper->toArray();

        $instructions = "Fill an Old Fashioned glass with a good amount of ice, then add equal parts Rakia, Campari and Sweet Vermouth before stirring well.";

        $this->assertSame('Rakia Negroni', $result['name']);
        $this->assertSame('If you are looking for a variation from Gin in your Negroni then you can’t go past the smooth, delicate flavour with subtle fruit undertones of Rakia. Made with the same proportions as the original, a Rakia Negroni has an absolutely amazing taste.', $result['description']);
        $this->assertSame('https://cocktailsdistilled.com/recipe/rakia-negroni/', $result['source']);
        $this->assertSame('Old Fashioned', $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame('Orange peel', $result['garnish']);
        $this->assertSame([], $result['tags']);
        $this->assertSame('Stir', $result['method']);
        $this->assertSame('https://cocktailsdistilled.com/wp-content/uploads/2018/06/IMG_6974-565x565.jpg', $result['images'][0]['url']);
        $this->assertSame('Cocktails Distilled', $result['images'][0]['copyright']);

        $this->assertSame(30.0, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('Rakia', $result['ingredients'][0]['name']);
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
