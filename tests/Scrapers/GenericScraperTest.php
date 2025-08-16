<?php

declare(strict_types=1);

namespace Tests\Scrapers;

use Tests\TestCase;
use Kami\Cocktail\Scraper\Sites\TuxedoNo2;
use Kami\Cocktail\Scraper\Sites\HausAlpenz;
use Kami\Cocktail\Scraper\Sites\LiberAndCo;
use Kami\Cocktail\Scraper\Sites\PunchDrink;
use Kami\Cocktail\Scraper\Sites\CocktailParty;
use Kami\Cocktail\Scraper\Sites\DiffordsGuide;
use Kami\Cocktail\Scraper\Sites\TheCocktailDB;
use PHPUnit\Framework\Attributes\DataProvider;
use Kami\Cocktail\Scraper\Sites\ImbibeMagazine;
use Kami\Cocktail\Scraper\Sites\MakeMeACocktail;
use Kami\Cocktail\Scraper\Sites\KindredCocktails;
use Kami\Cocktail\Scraper\Sites\SteveTheBartender;
use Kami\Cocktail\Scraper\Sites\CocktailsDistilled;
use Kami\Cocktail\Scraper\Sites\EricsCocktailGuide;

class GenericScraperTest extends TestCase
{
    #[DataProvider('provideFixtures')]
    public function testScrapeContent(string $url, string $class): void
    {
        $fixtureName = basename(str_replace('\\', '/', $class));
        $fixtureContent = file_get_contents(__DIR__ . '/../fixtures/sites/' . $fixtureName . '.html');

        $this->assertNotFalse($fixtureContent, 'Fixture content not found for ' . $class);

        $scraper = new $class($url, $fixtureContent);
        $result = $scraper->toArray();

        // Ensure that the result contains the expected structure for dynamic fields
        data_set($result, 'schema.recipe.images.*.uri', 'TEST');
        data_set($result, 'schema.ingredients.*._id', 'TEST');
        data_set($result, 'schema.recipe.ingredients.*._id', 'TEST');
        data_set($result, 'scraper_meta.*._id', 'TEST');

        $expected = json_decode(file_get_contents(__DIR__ . '/../fixtures/sites/' . $fixtureName . '.json'), true);
        $this->assertNotFalse($fixtureContent, 'Expected content not found for ' . $fixtureName);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<array<string>>
     */
    public static function provideFixtures(): array
    {
        return [
            ['https://punchdrink.com/recipes/negroni', PunchDrink::class],
            ['https://www.ericscocktailguide.com/recipes/negroni', EricsCocktailGuide::class],
            ['https://cocktailpartyapp.com/drinks/negroni/', CocktailParty::class],
            ['https://imbibemagazine.com/recipe/negroni-recipe/', ImbibeMagazine::class],
            ['https://cocktailsdistilled.com/recipe/kingston-negroni/', CocktailsDistilled::class],
            ['https://www.diffordsguide.com/cocktails/recipe/1392/negroni-cocktail', DiffordsGuide::class],
            ['https://alpenz.com/recipe-pHi4g8Upgz.html', HausAlpenz::class],
            ['https://kindredcocktails.com/cocktail/negroni', KindredCocktails::class],
            ['https://www.liberandcompany.com/products/strawberry-negroni', LiberAndCo::class],
            ['https://makemeacocktail.com/cocktail/6781/negroni', MakeMeACocktail::class],
            ['https://tuxedono2.com/negroni-cocktail-recipe', TuxedoNo2::class],
            ['https://www.thecocktaildb.com/drink/11003-Negroni', TheCocktailDB::class],
            ['https://stevethebartender.com.au/negroni-cocktail-recipe/', SteveTheBartender::class],
        ];
    }
}
