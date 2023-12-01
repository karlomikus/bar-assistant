<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\TestCase;
use Kami\Cocktail\Scraper\Manager;

class CocktailClubScraperTest extends TestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://cocktailclub.com/cocktails/negroni-tanqueray');
        $result = $scraper->toArray();

        $instructions = "1. Peel a large zest from a fresh orange and cut ready for garnish\n2. Fill your glass with cubed ice\n3. Pour in the equal measure of each ingredient\n4. Using your bar spoon, stir all the ingredients together well for about 20 sec.\n5. Spray the oils from the zest by twisting it over the drink for that citrus aroma.";

        // $this->assertSame('Negroni', $result['name']); // Unstable to fetch
        $this->assertSame('Sophisticated and simple at the&nbsp;same time with a complex flavor&nbsp;that makes the perfect aperitif.&nbsp;Popular all over the world and de&nbsp;rigeur during aperitivo hour in Milan&nbsp;&nbsp;', $result['description']);
        $this->assertSame('https://cocktailclub.com/cocktails/negroni-tanqueray', $result['source']);
        $this->assertSame(null, $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame(null, $result['garnish']);
        $this->assertSame(['Cocktail'], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertSame('https://embed-ssl.wistia.com/deliveries/7e5f5642fbaf503f1a41bae6957bc77f.jpg?image_crop_resized=1280x800', $result['images'][0]['url']);
        $this->assertSame('Cocktail Club', $result['images'][0]['copyright']);

        $this->assertSame(25.0, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('Gin', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(25.0, $result['ingredients'][1]['amount']);
        $this->assertSame('ml', $result['ingredients'][1]['units']);
        $this->assertSame('Aperitivo Bitters', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);

        $this->assertSame(25.0, $result['ingredients'][2]['amount']);
        $this->assertSame('ml', $result['ingredients'][2]['units']);
        $this->assertSame('Sweet Vermouth', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);
    }
}
