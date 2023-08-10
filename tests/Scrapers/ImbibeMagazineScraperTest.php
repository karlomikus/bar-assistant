<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Scraper\Manager;

class ImbibeMagazineScraperTest extends TestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://imbibemagazine.com/recipe/chocolate-strawberry-negroni/');
        $result = $scraper->toArray();

        $instructions = "1. Add all of the ingredients to mixing glass with 10-15 ice cubes, stir for 25 seconds, then strain over a large ice cube in a rocks glass. Express an orange peel over the drink and place in glass.\n2. Strawberry-Campari: Hull 1 1/2 lbs. of strawberries and chop into small pieces. Combine with 1 liter of Campari in a non-reactive container, and let the mix sit for 24 hours. Strain through fine mesh and press to get all the liquid out of the spent fruit.";

        $this->assertSame('Chocolate Strawberry Negroni', $result['name']);
        $this->assertSame('Fresh strawberries and a touch of crème de cacao lend sweet complexity to the classic.', $result['description']);
        $this->assertSame('https://imbibemagazine.com/recipe/chocolate-strawberry-negroni/', $result['source']);
        $this->assertSame('Rocks', $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame('Orange peel', $result['garnish']);
        $this->assertSame([], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertSame('https://imbibemagazine.com/wp-content/uploads/2021/08/chocolate-strawberry-negroni-poka-lola-social-club-crdt-tk.jpg', $result['images'][0]['url']);
        $this->assertSame('Imbibe Magazine', $result['images'][0]['copyright']);

        $this->assertSame(30, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('Gin', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(30, $result['ingredients'][1]['amount']);
        $this->assertSame('ml', $result['ingredients'][1]['units']);
        $this->assertSame('Strawberry Campari', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);

        $this->assertSame(22.5, $result['ingredients'][2]['amount']);
        $this->assertSame('ml', $result['ingredients'][2]['units']);
        $this->assertSame('Sweet vermouth', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);

        $this->assertSame(7.5, $result['ingredients'][3]['amount']);
        $this->assertSame('ml', $result['ingredients'][3]['units']);
        $this->assertSame('White creme de cacao', $result['ingredients'][3]['name']);
        $this->assertSame(false, $result['ingredients'][3]['optional']);
    }
}
