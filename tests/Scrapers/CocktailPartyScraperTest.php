<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\TestCase;
use Kami\Cocktail\Scraper\Manager;

class CocktailPartyScraperTest extends TestCase
{
    public function testScrapeNegroni(): void
    {
        $scraper = Manager::scrape('https://cocktailpartyapp.com/drinks/negroni/');
        $result = $scraper->toArray();

        $instructions = "Build in an ice-filled rocks glass – the order doesn’t matter. Just give it a stir and garnish with an orange twist.";

        $this->assertSame('Negroni', $result['name']);
        $this->assertSame("Cocktail snobs have a bad habit of using the Negroni as the threshold through which all drinkers must pass before they can be considered true connoisseurs. There’s an element of truth to this view; the balance of flavors requires an experienced tongue to appreciate. But make no mistake – it is a very bitter drink, and it’s not for everyone.\n\nSome folks have a genetic variation which allows them to taste bitter compounds, while other people don’t. Those who can are called “supertasters,” and it’s likely that no amount of epicurean taste-acquiring can make this drink palatable to those so afflicted.", $result['description']);
        $this->assertSame('https://cocktailpartyapp.com/drinks/negroni/', $result['source']);
        $this->assertSame(null, $result['glass']);
        $this->assertSame($instructions, $result['instructions']);
        $this->assertSame(null, $result['garnish']);
        $this->assertSame(['Simple Cocktails', 'Spirit-Forward Cocktails'], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertSame('https://cocktailpartyapp.com/wp-content/uploads/Negroni.webp', $result['images'][0]['url']);
        $this->assertSame('CocktailParty', $result['images'][0]['copyright']);

        $this->assertSame(1.5, $result['ingredients'][0]['amount']);
        $this->assertSame('oz', $result['ingredients'][0]['units']);
        $this->assertSame('Bitter orange apéritif', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(1.5, $result['ingredients'][1]['amount']);
        $this->assertSame('oz', $result['ingredients'][1]['units']);
        $this->assertSame('Sweet vermouth', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);

        $this->assertSame(1.5, $result['ingredients'][2]['amount']);
        $this->assertSame('oz', $result['ingredients'][2]['units']);
        $this->assertSame('Gin', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);
    }

    public function testScrapeManhattanBianco(): void
    {
        $scraper = Manager::scrape('https://cocktailpartyapp.com/drinks/manhattan-bianco/');
        $result = $scraper->toArray();

        $this->assertSame('Manhattan Bianco', $result['name']);
        $this->assertSame("Changing up the type of vermouth called for in a Manhattan is a time-honored way of producing amazing variations on a classic. The Manhattan Bianco is an excellent example of this technique, with floral blanc (or bianco) vermouth taking the place of the original’s sweet vermouth.\n\nIt’s a major change in the flavor profile and, quite frankly, isn’t much like a Manhattan at all. But it is a genuinely lovely drink if you enjoy blanc vermouth. With bourbon or blended whiskey, it leans sweeter; with rye, the bright pepper notes come through. Since there are no bitters, the lemon oil from the twist does a lot of work here, and we consider it an essential ingredient.", $result['description']);
        $this->assertSame('https://cocktailpartyapp.com/drinks/manhattan-bianco/', $result['source']);
        $this->assertSame(null, $result['glass']);
        $this->assertSame('Stir all ingredients with ice. Strain into a chilled cocktail or coupe glass. Garnish with a lemon twist.', $result['instructions']);
        $this->assertSame(null, $result['garnish']);
        $this->assertSame(['Simple Cocktails', 'Spirit-Forward Cocktails'], $result['tags']);
        $this->assertSame(null, $result['method']);
        $this->assertSame('https://cocktailpartyapp.com/wp-content/uploads/Manhattan-Bianco.webp', $result['images'][0]['url']);
        $this->assertSame('CocktailParty', $result['images'][0]['copyright']);

        $this->assertSame(1.5, $result['ingredients'][0]['amount']);
        $this->assertSame('oz', $result['ingredients'][0]['units']);
        $this->assertSame('Bourbon', $result['ingredients'][0]['name']);
        $this->assertSame(false, $result['ingredients'][0]['optional']);

        $this->assertSame(1.5, $result['ingredients'][1]['amount']);
        $this->assertSame('oz', $result['ingredients'][1]['units']);
        $this->assertSame('Blanc vermouth', $result['ingredients'][1]['name']);
        $this->assertSame(false, $result['ingredients'][1]['optional']);

        $this->assertSame(1.0, $result['ingredients'][2]['amount']);
        $this->assertSame('piece', $result['ingredients'][2]['units']);
        $this->assertSame('Lemon peel', $result['ingredients'][2]['name']);
        $this->assertSame(false, $result['ingredients'][2]['optional']);
    }
}
