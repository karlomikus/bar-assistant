<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\TestCase;
use Kami\Cocktail\Scraper\Manager;
use PHPUnit\Framework\Attributes\DataProvider;

class GenericScraperTest extends TestCase
{
    #[DataProvider('provideUrls')]
    public function testScrape($url): void
    {
        // We test simple data here, it's too much work to keep everything in sync with the website changes
        $scraper = Manager::scrape($url);
        $result = $scraper->toArray();
        $recipe = $result['schema']['recipe'];
        $ingredients = $result['schema']['ingredients'];

        $this->assertNotEmpty($recipe['name']);
        $this->assertNotEmpty($recipe['ingredients']);
        $this->assertNotEmpty($ingredients);
    }

    public static function provideUrls(): array
    {
        return [
            ['https://www.ericscocktailguide.com/recipes/negroni'],
            ['https://cocktailpartyapp.com/drinks/negroni/'],
            ['https://cocktailsdistilled.com/recipe/rakia-negroni/'],
            ['https://www.diffordsguide.com/cocktails/recipe/1392/negroni-cocktail'],
            ['https://alpenz.com/recipe-ikfCvvprSL.html'],
            ['https://imbibemagazine.com/recipe/chocolate-strawberry-negroni'],
            ['https://kindredcocktails.com/cocktail/negroni'],
            ['https://www.liberandcompany.com/products/strawberry-negroni'],
            ['https://makemeacocktail.com/cocktail/6781/negroni'],
            ['https://punchdrink.com/recipes/milano-torino'],
            ['https://stevethebartender.com.au/oaxacanite-cocktail-recipe'],
            ['https://www.thecocktaildb.com/drink/11003-Negroni'],
            ['https://tuxedono2.com/negroni-cocktail-recipe'],
        ];
    }
}
