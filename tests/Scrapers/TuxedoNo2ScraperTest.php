<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Throwable;
use Tests\ScraperTestCase;
use Kami\Cocktail\Scraper\Manager;

class TuxedoNo2ScraperTest extends ScraperTestCase
{
    public function testScrape(): void
    {
        try {
            $scraper = Manager::scrape('https://tuxedono2.com/negroni-cocktail-recipe');
        } catch (Throwable $e) {
            $this->markTestSkipped('Scraping failed: ' . $e->getMessage());
        }

        $result = $scraper->toArray();

        $instructions = "1. Combine all ingredients with ice and stir\n2. Strain into an old fashioned over ice\n3. Garnish with citrus peel";

        $this->assertSame('Negroni', $result['name']);
        $this->assertSame('The ultimate shift-ending cocktail and champion of bitterness', $result['description']);
        $this->assertSame('https://tuxedono2.com/negroni-cocktail-recipe', $result['source']);
        $this->assertSame('rocks glass', $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame('grapefruit peel or orange peel for garnish', $result['garnish']);
        $this->assertSame(['Gin'], $result['tags']);
        $this->assertSame('stirred', $result['method']);
        $this->assertSame('https://d34nm4jmyicdxh.cloudfront.net/eyJidWNrZXQiOiJjaHJpc3N5LXR1eGVkby1ubzIiLCJrZXkiOiJyZWNpcGUtbmVncm9uaS1jb2NrdGFpbC1yZWNpcGUuanBnIiwiZWRpdHMiOnsicmVzaXplIjp7IndpZHRoIjoxMjAwLCJoZWlnaHQiOjEyMDAsImZpdCI6ImNvdmVyIn19fQ==', $result['images'][0]['url']);
        $this->assertSame('TuxedoNo2', $result['images'][0]['copyright']);

        $this->assertSame(30.0, $result['ingredients'][0]['amount']);
        $this->assertSame('ml', $result['ingredients'][0]['units']);
        $this->assertSame('London dry gin', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(30.0, $result['ingredients'][1]['amount']);
        $this->assertSame('ml', $result['ingredients'][1]['units']);
        $this->assertSame('Campari', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);

        $this->assertSame(30.0, $result['ingredients'][2]['amount']);
        $this->assertSame('ml', $result['ingredients'][2]['units']);
        $this->assertSame('Sweet vermouth', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);
    }
}
