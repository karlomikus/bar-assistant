<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Glass;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    // public function setUp(): void
    // {
    //     parent::setUp();

    //     $this->actingAs(
    //         User::factory()->create()
    //     );
    // }

    // public function test_cocktail_default_import_from_array()
    // {
    //     $bar = $this->setupBar();
    //     $existingIngredient = Ingredient::factory()
    //         ->state([
    //             'name' => 'Ingredient 1',
    //             'strength' => 45.5,
    //             'description' => 'Test',
    //             'bar_id' => $bar->id,
    //         ])
    //         ->create();

    //     $method = CocktailMethod::factory()->create([
    //         'name' => 'Method name',
    //         'bar_id' => $bar->id,
    //     ]);

    //     $glass = Glass::factory()->create([
    //         'name' => 'Glass name',
    //         'bar_id' => $bar->id,
    //     ]);

    //     $service = $this->getService();
    //     $importArray = [
    //         'name' => 'Cocktail name',
    //         'instructions' => 'Instruction data',
    //         'description' => 'Description data',
    //         'source' => 'Laravel',
    //         'garnish' => 'Garnish data',
    //         'tags' => ['Tag 1', 'Tag 2'],
    //         'method' => 'Method name',
    //         'glass' => 'Glass name',
    //         'images' => [
    //             ['url' => UploadedFile::fake()->image('image1.jpg'), 'copyright' => 'Image copyright 1'],
    //             ['url' => null, 'copyright' => 'Null'],
    //             ['url' => UploadedFile::fake()->image('image2.png')],
    //         ],
    //         'ingredients' => [
    //             [
    //                 'name' => 'Ingredient 1',
    //                 'amount' => 30,
    //                 'units' => 'ml',
    //                 'optional' => true,
    //                 'substitutes' => [],
    //                 'strength' => 45.5,
    //                 'description' => 'Existing ingredient',
    //                 'origin' => 'Laravel test suite',
    //             ],
    //             [
    //                 'name' => 'New ingredient',
    //                 'amount' => 22.5,
    //                 'units' => 'ml',
    //                 'optional' => false,
    //                 'substitutes' => ['Ingredient 1'],
    //                 'strength' => 40,
    //                 'description' => 'New ingredient description',
    //                 'origin' => 'Laravel test suite',
    //             ]
    //         ]
    //     ];

    //     $cocktail = $service->importCocktailFromArray($importArray, auth()->user()->id, $bar->id);

    //     $this->assertSame('Cocktail name', $cocktail->name);
    //     $this->assertSame('Instruction data', $cocktail->instructions);
    //     $this->assertSame('Description data', $cocktail->description);
    //     $this->assertSame('Garnish data', $cocktail->garnish);
    //     $this->assertSame('Laravel', $cocktail->source);
    //     $this->assertSame($method->id, $cocktail->cocktail_method_id);
    //     $this->assertSame($glass->id, $cocktail->glass_id);
    //     $this->assertSame('Tag 1', $cocktail->tags->first()->name);
    //     $this->assertSame('Tag 2', $cocktail->tags->last()->name);

    //     $this->assertSame($existingIngredient->id, $cocktail->ingredients->first()->ingredient_id);
    //     $this->assertSame(30, $cocktail->ingredients->first()->amount);
    //     $this->assertSame('ml', $cocktail->ingredients->first()->units);
    //     $this->assertTrue((bool) $cocktail->ingredients->first()->optional);
    //     $this->assertCount(0, $cocktail->ingredients->first()->substitutes);

    //     $this->assertSame(2, $cocktail->ingredients->last()->ingredient_id);
    //     $this->assertSame('New ingredient', $cocktail->ingredients->last()->ingredient->name);
    //     $this->assertSame(40, $cocktail->ingredients->last()->ingredient->strength);
    //     $this->assertSame('New ingredient description', $cocktail->ingredients->last()->ingredient->description);
    //     $this->assertSame('Laravel test suite', $cocktail->ingredients->last()->ingredient->origin);
    //     $this->assertSame(22.5, $cocktail->ingredients->last()->amount);
    //     $this->assertSame('ml', $cocktail->ingredients->last()->units);
    //     $this->assertFalse((bool) $cocktail->ingredients->last()->optional);
    //     $this->assertCount(1, $cocktail->ingredients->last()->substitutes);

    //     $this->assertCount(2, $cocktail->images);
    //     $this->assertSame('jpg', $cocktail->images->first()->file_extension);
    //     $this->assertSame('Image copyright 1', $cocktail->images->first()->copyright);
    //     $this->assertSame('png', $cocktail->images->last()->file_extension);
    //     $this->assertNull($cocktail->images->last()->copyright);
    // }

    // private function getService(): ImportService
    // {
    //     return resolve(ImportService::class);
    // }
}
