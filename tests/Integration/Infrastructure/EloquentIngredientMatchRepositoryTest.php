<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use BarAssistant\Domain\Bar\BarId;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentIngredientMatchRepository;

final class EloquentIngredientMatchRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_matches_for_the_requested_bar(): void
    {
        $membership = $this->setupBarMembership();
        $firstIngredient = Ingredient::factory()->for($membership->bar)->create(['name' => 'Gin']);
        $secondIngredient = Ingredient::factory()->for($membership->bar)->create(['name' => 'Campari']);
        Ingredient::factory()->create(['name' => 'Foreign ingredient']);

        $repository = new EloquentIngredientMatchRepository();
        $matches = $repository->findManyByBarId(new BarId($membership->bar_id));

        $this->assertCount(2, $matches);
        $this->assertEqualsCanonicalizing(
            [$firstIngredient->id, $secondIngredient->id],
            array_map(static fn ($match): int => $match->getId()->value, $matches),
        );
        $this->assertEqualsCanonicalizing(
            ['Gin', 'Campari'],
            array_map(static fn ($match): string => $match->getName()->toString(), $matches),
        );
    }

    public function test_it_returns_empty_array_when_bar_has_no_ingredients(): void
    {
        $membership = $this->setupBarMembership();
        Ingredient::factory()->create();

        $repository = new EloquentIngredientMatchRepository();
        $matches = $repository->findManyByBarId(new BarId($membership->bar_id));

        $this->assertSame([], $matches);
    }
}
