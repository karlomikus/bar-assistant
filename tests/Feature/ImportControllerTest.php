<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_cocktail_scrape_from_valid_url()
    {
        $response = $this->postJson('/api/import/cocktail', [
            'source' => 'https://punchdrink.com/recipes/whiskey-peach-smash/'
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Whiskey Peach Smash');
        $response->assertJsonCount(1, 'data.images');
        $response->assertJsonCount(5, 'data.ingredients');
    }

    public function test_cocktail_scrape_fails_for_unknown_url()
    {
        $response = $this->postJson('/api/import/cocktail', [
            'source' => 'https://google.com'
        ]);

        $response->assertNotFound();
    }

    public function test_cocktail_scrape_from_JSON()
    {
        $response = $this->postJson('/api/import/cocktail?type=json', [
            'source' => '{"name":"Alexander","instructions":"1. Pour all ingredients into cocktail shaker filled with ice cubes.\n2. Shake and strain into a chilled cocktail glass.","garnish":"Sprinkle ground nutmeg on top.","description":"The Alexander cocktail was born in London in 1922 by Hery Mc Elhone, at Ciro\u2019s Club in honor of a famous bride, at the beginning it was called Panama, Gin was used instead of Cognac and light cocoa cream instead of dark.\n\nThroughout its history, Alexander has given rise to many other variations:\n\n**Grasshopper**: This variant is also part of the Iba list and involves the use of cr\u00e8me de menthe verde instead of cognac.\n\n**Alexandra**: Use the light cocoa cream instead of the dark one and replace the nutmeg with cocoa.\n\n**Alexander\u2019s Sister**: Use cr\u00e8me de menthe instead of cr\u00e8me de cacao\n\n**Alejandro**: Replace cognac with rum","source":"https:\/\/iba-world.com\/alexander\/","tags":["IBA Cocktail","The Unforgettables","Brandy"],"glass":"Coupe","method":"Shake","images":[{"url":"http:\/\/localhost:8000\/uploads\/cocktails\/alexander_ZjD02V.jpg","copyright":"Liquor.com \/ Tim Nusog","sort":0}],"ingredients":[{"sort":1,"name":"Cognac","amount":30,"units":"ml","optional":false,"category":"Spirits","description":"A variety of brandy named after the commune of Cognac, France.","strength":40,"origin":"France","substitutes":[]},{"sort":2,"name":"Dark Cr\u00e8me de Cacao","amount":30,"units":"ml","optional":false,"category":"Liqueurs","description":"Dark brown creamy chocolate-flavored liqueur made from cacao seed.","strength":25,"origin":"France","substitutes":[]},{"sort":3,"name":"Cream","amount":30,"units":"ml","optional":false,"category":"Uncategorized","description":"Cream is a dairy product composed of the higher-fat layer skimmed from the top of milk before homogenization.","strength":0,"origin":null,"substitutes":[]}]}'
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Alexander');
    }
}
