<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\DomainEventSubscriber;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\Models\CocktailIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use BarAssistant\Domain\Cocktail\Event\CocktailMethodUpdated;
use Kami\Cocktail\Infrastructure\DomainEventSubscriber\CocktailMethodUpdatedRecalculateAbvSubscriber;

final class CocktailMethodUpdatedRecalculateAbvSubscriberTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_recalculates_abv_when_dilution_changes(): void
    {
        $membership = $this->setupBarMembership();
        $method = CocktailMethod::factory()->recycle($membership->bar)->create(['dilution_percentage' => 20.0]);
        $ingredient = Ingredient::factory()->recycle($membership->bar)->create(['strength' => 40.0]);
        $cocktail = Cocktail::factory()->recycle($membership->bar)->create([
            'cocktail_method_id' => $method->id,
            'abv' => 0.0,
        ]);
        CocktailIngredient::factory()->for($cocktail)->for($ingredient)->create([
            'amount' => 60,
            'amount_max' => null,
            'units' => 'ml',
        ]);

        $subscriber = new CocktailMethodUpdatedRecalculateAbvSubscriber();
        $subscriber->handle(new CocktailMethodUpdated(
            barId: $membership->bar_id,
            methodId: $method->id,
            previousDilutionPercentage: 10.0,
            currentDilutionPercentage: 20.0,
        ));

        $cocktail->refresh();

        $this->assertNotNull($cocktail->abv);
        $this->assertSame($cocktail->getABV(), $cocktail->abv);
    }

    public function test_it_skips_recalculation_when_dilution_is_unchanged(): void
    {
        $membership = $this->setupBarMembership();
        $method = CocktailMethod::factory()->recycle($membership->bar)->create(['dilution_percentage' => 15.0]);
        $cocktail = Cocktail::factory()->recycle($membership->bar)->create([
            'cocktail_method_id' => $method->id,
            'abv' => 7.77,
        ]);

        $subscriber = new CocktailMethodUpdatedRecalculateAbvSubscriber();
        $subscriber->handle(new CocktailMethodUpdated(
            barId: $membership->bar_id,
            methodId: $method->id,
            previousDilutionPercentage: 15.0,
            currentDilutionPercentage: 15.0,
        ));

        $this->assertSame(7.77, $cocktail->fresh()->abv);
    }
}
