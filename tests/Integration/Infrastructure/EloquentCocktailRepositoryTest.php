<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use DateTimeImmutable;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use Kami\Cocktail\Models\Ingredient;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Dilution;
use Kami\Cocktail\Models\CocktailMethod;
use BarAssistant\Domain\Cocktail\GlassId;
use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\MethodId;
use BarAssistant\Domain\Cocktail\PublicId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\PublicStatus;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use BarAssistant\Domain\Cocktail\CocktailIngredient;
use Kami\Cocktail\Models\Cocktail as ModelsCocktail;
use Kami\Cocktail\Infrastructure\EloquentCocktailRepository;
use BarAssistant\Domain\Cocktail\CocktailIngredientSubstitute;

class EloquentCocktailRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_cocktail(): void
    {
        $membership = $this->setupBarMembership();
        $ingredient = Ingredient::factory()->for($membership->bar)->create([
            'strength' => 45.0
        ]);
        $parentCocktail = ModelsCocktail::factory()->for($membership->bar)->create();
        $subIngredient = Ingredient::factory()->for($membership->bar)->create();
        $cocktailMethod = CocktailMethod::factory()->for($membership->bar)->create(['id' => 30]);
        $glass = Glass::factory()->for($membership->bar)->create(['id' => 20]);
        $image = Image::factory()->create();

        $cocktail = Cocktail::create(
            barId: new BarId($membership->bar_id),
            name: Name::fromString('Koktel 1'),
            garnish: 'Garnish',
            description: 'Desc',
            instructions: "1. Add to glass\n2. Stir",
            authors: Authors::createdBy(new UserId($membership->user_id)),
            recordTimestamps: RecordTimestamps::createdNow(),
            dilution: Dilution::fromFloat(10.0),
            source: 'https://barassistant.app',
            year: 2020,
            glassId: new GlassId($glass->id),
            methodId: new MethodId($cocktailMethod->id),
            publicStatus: PublicStatus::createFrom(PublicId::createFrom('test'), new DateTimeImmutable('2020-01-01'), new DateTimeImmutable('2020-02-01')),
            variantOf: new CocktailId($parentCocktail->id),
        );

        $cocktail->addIngredient(CocktailIngredient::create(
            ingredientId: new IngredientId($ingredient->id),
            amountWithUnits: AmountWithUnits::from(30, Unit::from('ml'), 60),
            abv: ABV::from($ingredient->strength),
            note: 'Note',
            isSpecific: true,
            isOptional: true,
            substitutes: [
                CocktailIngredientSubstitute::create(
                    ingredientId: new IngredientId($subIngredient->id),
                    amountWithUnits: AmountWithUnits::from(7.5, Unit::from('ml'), 15),
                ),
            ]
        ));

        $cocktail->addImage(new ImageId($image->id));

        $repo = new EloquentCocktailRepository();
        $cocktail = $repo->save($cocktail);

        $this->assertDatabaseCount('cocktails', 2);
        $this->assertDatabaseHas('cocktails', [
            'name' => (string) $cocktail->getName(),
            'slug' => 'koktel-1-1',
            'instructions' => $cocktail->getInstructions(),
            'garnish' => $cocktail->getGarnish(),
            'description' => $cocktail->getDescription(),
            'source' => $cocktail->getSource(),
            'bar_id' => $membership->bar_id,
            'created_user_id' => $membership->user_id,
            'updated_user_id' => null,
            'glass_id' => $glass->id,
            'cocktail_method_id' => $cocktailMethod->id,
            'public_id' => 'test',
            'public_at' => '2020-01-01 00:00:00',
            'public_expires_at' => '2020-02-01 00:00:00',
            'parent_cocktail_id' => $parentCocktail->id,
            'abv' => 40.91,
            'year' => 2020,
        ]);

        $this->assertDatabaseCount('cocktail_ingredients', 1);
        $this->assertDatabaseHas('cocktail_ingredients', [
            'ingredient_id' => $ingredient->id,
            'amount' => 30,
            'units' => 'ml',
            'optional' => true,
            'sort' => 0,
            'amount_max' => 60,
            'note' => 'Note',
            'is_specified' => true,
        ]);

        $this->assertDatabaseCount('cocktail_ingredient_substitutes', 1);
        $this->assertDatabaseHas('cocktail_ingredient_substitutes', [
            'ingredient_id' => $subIngredient->id,
            'amount' => 7.5,
            'units' => 'ml',
            'amount_max' => 15,
        ]);

        $this->assertDatabaseCount('images', 1);
        $this->assertDatabaseHas('images', [
            'imageable_type' => ModelsCocktail::class,
            'imageable_id' => $cocktail->getId()->value,
        ]);
    }
}
