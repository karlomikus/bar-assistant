<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Glass;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Import\FromArray;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\CocktailMethod;
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
        $this->assertSame(41.17, $cocktail->abv);
        $this->assertSame($method->id, $cocktail->cocktail_method_id);
        $this->assertSame($glass->id, $cocktail->glass_id);
        $this->assertSame('Tag 1', $cocktail->tags->first()->name);
        $this->assertSame('Tag 2', $cocktail->tags->last()->name);

        // Ingredient 1
        $this->assertSame(1, $cocktail->ingredients->first()->sort);
        $this->assertSame($existingIngredient->id, $cocktail->ingredients->first()->ingredient_id);
        $this->assertSame(30, $cocktail->ingredients->first()->amount);
        $this->assertNull($cocktail->ingredients->first()->amount_max);
        $this->assertNull($cocktail->ingredients->first()->note);
        $this->assertSame('ml', $cocktail->ingredients->first()->units);
        $this->assertFalse((bool) $cocktail->ingredients->first()->optional);
        $this->assertCount(0, $cocktail->ingredients->first()->substitutes);

        // Ingredient 2
        $this->assertSame(2, $cocktail->ingredients->last()->sort);
        $this->assertSame(2, $cocktail->ingredients->last()->ingredient_id);
        $this->assertSame('New ingredient', $cocktail->ingredients->last()->ingredient->name);
        $this->assertSame(40, $cocktail->ingredients->last()->ingredient->strength);
        $this->assertSame('New ingredient description', $cocktail->ingredients->last()->ingredient->description);
        $this->assertSame('Laravel test suite', $cocktail->ingredients->last()->ingredient->origin);
        $this->assertSame(2, $cocktail->ingredients->last()->amount);
        $this->assertSame(4, $cocktail->ingredients->last()->amount_max);
        $this->assertSame('Use best one', $cocktail->ingredients->last()->note);
        $this->assertSame('dashes', $cocktail->ingredients->last()->units);
        $this->assertTrue((bool) $cocktail->ingredients->last()->optional);
        $this->assertCount(2, $cocktail->ingredients->last()->substitutes);

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
