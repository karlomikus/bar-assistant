<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\TestCase;
use Kami\Cocktail\Scraper\Manager;

class DiffordsGuideScraperTest extends TestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://www.diffordsguide.com/cocktails/recipe/1392/negroni-cocktail');
        $result = $scraper->toArray();

        $instructions = "1. POUR all ingredients into ice-filled glass and STIR";

        $this->assertSame('Negroni Cocktail', $result['name']);
        $this->assertNotNull($result['description']);
        $this->assertSame('https://www.diffordsguide.com/cocktails/recipe/1392/negroni-cocktail', $result['source']);
        $this->assertSame('Old-fashioned glass', $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame('Orange wedge or slice and/or orange zest twist', $result['garnish']);
        $this->assertSame([], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertSame('https://cdn.diffords.com/contrib/stock-images/2019/06/5cfa92d91f3eb.jpg', $result['images'][0]['url']);
        $this->assertSame('Simon Difford', $result['images'][0]['copyright']);

        $this->assertSame(30, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('Gin', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(30, $result['ingredients'][1]['amount']);
        $this->assertSame('ml', $result['ingredients'][1]['units']);
        $this->assertSame('Campari or other red bitter liqueur', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);

        $this->assertSame(30, $result['ingredients'][2]['amount']);
        $this->assertSame('ml', $result['ingredients'][2]['units']);
        $this->assertSame('Vermouth - Rosso/rouge  vermouth', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);
    }
}
