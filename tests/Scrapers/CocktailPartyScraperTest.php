<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Scraper\Manager;

class CocktailPartyScraperTest extends TestCase
{
    public function testScrape(): void
    {
        $scraper = Manager::scrape('https://cocktailpartyapp.com/drinks/negroni/');
        $result = $scraper->toArray();

        $instructions = "Build in an ice-filled rocks glass – the order doesn’t matter. Just give it a stir and garnish with an orange twist.";

        $this->assertSame('Negroni', $result['name']);
        $this->assertSame('Cocktail snobs have a bad habit of using the Negroni as the threshold through which all drinkers must pass before they can be considered true connoisseurs. There’s an element of truth to this view; the balance of flavors requires an experienced tongue to appreciate. But make no mistake – it is a very bitter drink, and it’s not for everyone. Some folks have a genetic variation which allows them to taste bitter compounds, while other people don’t. Those who can are called “supertasters,” and it’s likely that no amount of epicurean taste-acquiring can make this drink palatable to those so afflicted.', $result['description']);
        $this->assertSame('https://cocktailpartyapp.com/drinks/negroni/', $result['source']);
        $this->assertSame(null, $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame(null, $result['garnish']);
        $this->assertSame([], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertSame('https://cocktailpartyapp.com/wp-content/uploads/Negroni.webp', $result['images'][0]['url']);
        $this->assertSame('CocktailParty', $result['images'][0]['copyright']);

        $this->assertSame(1, $result['ingredients'][0]['amount']);
        $this->assertSame('parts', $result['ingredients'][0]['units']);
        $this->assertSame('Bitter orange apéritif', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(1, $result['ingredients'][1]['amount']);
        $this->assertSame('parts', $result['ingredients'][1]['units']);
        $this->assertSame('Sweet vermouth', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);

        $this->assertSame(1, $result['ingredients'][2]['amount']);
        $this->assertSame('parts', $result['ingredients'][2]['units']);
        $this->assertSame('Gin', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);
    }
}
