<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Scraper\Manager;

class LiberAndCoScraperTest extends TestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://www.liberandcompany.com/products/strawberry-negroni');
        $result = $scraper->toArray();

        $instructions = "1. Add all ingredients to a mixing glass\n2. Add ice and stir for 10 seconds\n3. Strain into a rocks glass over a large format ice cube";

        $this->assertSame('Strawberry Negroni', $result['name']);
        $this->assertSame('Strawberry is the perfect complement to the classic Negroni profile. Bitter orange and citrus, along with the spice of sweet vermouth add layers of complexity. This cocktail is a stunner and is a must-try-- you will not be disappointed!', $result['description']);
        $this->assertSame('https://www.liberandcompany.com/products/strawberry-negroni', $result['source']);
        $this->assertSame('rocks', $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame('strawberry', $result['garnish']);
        $this->assertSame([], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertSame('https://www.liberandcompany.com/cdn/shop/files/StrawberryNegroni2000_1200x.jpg?v=1690574513', $result['images'][0]['url']);
        $this->assertSame('LiberAndCo', $result['images'][0]['copyright']);

        $this->assertSame(15, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('Pacific Strawberry Syrup', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(30, $result['ingredients'][1]['amount']);
        $this->assertSame('ml', $result['ingredients'][1]['units']);
        $this->assertSame('Gin', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);

        $this->assertSame(22.5, $result['ingredients'][2]['amount']);
        $this->assertSame('ml', $result['ingredients'][2]['units']);
        $this->assertSame('Campari', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);

        $this->assertSame(15, $result['ingredients'][3]['amount']);
        $this->assertSame('ml', $result['ingredients'][3]['units']);
        $this->assertSame('Vermouth', $result['ingredients'][3]['name']);
        $this->assertSame(false, $result['ingredients'][3]['optional']);
    }
}
