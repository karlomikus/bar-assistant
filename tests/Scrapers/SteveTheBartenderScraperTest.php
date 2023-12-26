<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\TestCase;
use Kami\Cocktail\Scraper\Manager;

class SteveTheBartenderScraperTest extends TestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://stevethebartender.com.au/oaxacanite-cocktail-recipe/');
        $result = $scraper->toArray();

        $this->assertSame('Oaxacanite', $result['name']);
        $this->assertSame('Sasha Petraske wasn\'t a huge fan of mezcal cocktails as he believed it was too dominate in cocktails. Whilst working at the John Dory Oyster Bar, where Sasha was consulting, Ben Long set forth to change this with the Oaxacanite with great success.A tequila and mezcal split base version of a Bees Knees, almost.Featured in Sasha Petraske Regarding Cocktails.', $result['description']);
        $this->assertSame('https://stevethebartender.com.au/oaxacanite-cocktail-recipe/', $result['source']);
        $this->assertSame(null, $result['glass']);
        $this->assertSame("1. Combine all the ingredients in your cocktail shaker\n2. Shake with ice for 12-15 seconds\n3. Double strain into a chilled cocktail glass\n4. Garnish with a twist of grapefruit", $result['instructions']);
        $this->assertSame(null, $result['garnish']);
        $this->assertSame([], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertSame('https://stevethebartender.com.au/wp-content/uploads/2021/12/oaxacanite-cocktail.jpg', $result['images'][0]['url']);
        $this->assertSame('Steve the Bartender', $result['images'][0]['copyright']);

        $this->assertSame(30.0, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('Mezcal', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(30.0, $result['ingredients'][1]['amount']);
        $this->assertSame('ml', $result['ingredients'][1]['units']);
        $this->assertSame('Blanco Tequila', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);
    }
}
