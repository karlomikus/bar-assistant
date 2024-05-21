<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Glass;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\External\Import\FromArray;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FromArrayTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_process(): void
    {
        $bar = $this->setupBar();
        $importer = $this->getImporter();

        $existingIngredient = Ingredient::factory()
            ->state([
                'name' => 'Ingredient 1',
                'strength' => 45.5,
                'description' => 'Test',
                'bar_id' => $bar->id,
            ])
            ->create();

        $method = CocktailMethod::factory()->create([
            'name' => 'Method name',
            'bar_id' => $bar->id,
            'dilution_percentage' => 10
        ]);

        $glass = Glass::factory()->create([
            'name' => 'Glass name',
            'bar_id' => $bar->id,
        ]);

        $importData = json_decode(file_get_contents(base_path('tests/fixtures/import.json')), true);
        $importData['images'] = [
            ['url' => UploadedFile::fake()->image('image1.jpg'), 'copyright' => 'Image copyright 1'],
            ['url' => null, 'copyright' => 'Null'],
            ['url' => UploadedFile::fake()->image('image2.png')],
        ];

        $cocktail = $importer->process(
            $importData,
            auth()->user()->id,
            $bar->id
        );

        $this->assertSame('Cocktail name', $cocktail->name);
        $this->assertSame('Instruction data', $cocktail->instructions);
        $this->assertSame('Description data', $cocktail->description);
        $this->assertSame('Garnish data', $cocktail->garnish);
        $this->assertSame('Laravel', $cocktail->source);
        $this->assertSame(36.82, $cocktail->abv);
        $this->assertSame($method->id, $cocktail->cocktail_method_id);
        $this->assertSame($glass->id, $cocktail->glass_id);
        $this->assertSame('Tag 1', $cocktail->tags->first()->name);
        $this->assertSame('Tag 2', $cocktail->tags->last()->name);
        $this->assertCount(0, $cocktail->utensils);

        // Ingredient 1
        $this->assertSame(1, $cocktail->ingredients[0]->sort);
        $this->assertSame($existingIngredient->id, $cocktail->ingredients[0]->ingredient_id);
        $this->assertSame(30.0, $cocktail->ingredients[0]->amount);
        $this->assertNull($cocktail->ingredients[0]->amount_max);
        $this->assertNull($cocktail->ingredients[0]->note);
        $this->assertSame('ml', $cocktail->ingredients[0]->units);
        $this->assertFalse((bool) $cocktail->ingredients[0]->optional);
        $this->assertCount(0, $cocktail->ingredients[0]->substitutes);

        // Ingredient 2
        $this->assertSame(2, $cocktail->ingredients[1]->sort);
        $this->assertSame(2, $cocktail->ingredients[1]->ingredient_id);
        $this->assertSame('New ingredient', $cocktail->ingredients[1]->ingredient->name);
        $this->assertSame(40.0, $cocktail->ingredients[1]->ingredient->strength);
        $this->assertSame('New ingredient description', $cocktail->ingredients[1]->ingredient->description);
        $this->assertSame('Laravel test suite', $cocktail->ingredients[1]->ingredient->origin);
        $this->assertSame(2.0, $cocktail->ingredients[1]->amount);
        $this->assertSame(4.0, $cocktail->ingredients[1]->amount_max);
        $this->assertSame('Use best one', $cocktail->ingredients[1]->note);
        $this->assertSame('dashes', $cocktail->ingredients[1]->units);
        $this->assertTrue((bool) $cocktail->ingredients[1]->optional);
        $this->assertCount(2, $cocktail->ingredients[1]->substitutes);

        // Ingredient 3
        $this->assertSame(3, $cocktail->ingredients[2]->sort);
        $this->assertSame('Detailed subs', $cocktail->ingredients[2]->ingredient->name);
        $this->assertSame(35.5, $cocktail->ingredients[2]->ingredient->strength);
        $this->assertSame(30.0, $cocktail->ingredients[2]->amount);
        $this->assertSame('ml', $cocktail->ingredients[2]->units);
        $this->assertCount(1, $cocktail->ingredients[2]->substitutes);
        $this->assertSame('Overproof Rum', $cocktail->ingredients[2]->substitutes[0]->ingredient->name);
        $this->assertSame(50.0, $cocktail->ingredients[2]->substitutes[0]->ingredient->strength);
        $this->assertSame(30, $cocktail->ingredients[2]->substitutes[0]->amount);
        $this->assertSame(45, $cocktail->ingredients[2]->substitutes[0]->amount_max);
        $this->assertSame('ml', $cocktail->ingredients[2]->substitutes[0]->units);

        // Images
        $this->assertCount(2, $cocktail->images);
        $this->assertSame('jpg', $cocktail->images->first()->file_extension);
        $this->assertSame('Image copyright 1', $cocktail->images->first()->copyright);
        $this->assertSame('png', $cocktail->images->last()->file_extension);
        $this->assertNull($cocktail->images->last()->copyright);
    }

    private function getImporter(): FromArray
    {
        return resolve(FromArray::class);
    }
}
