<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use BarAssistant\Domain\Bar\BarId;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentCocktailMatchRepository;

final class EloquentCocktailMatchRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_matches_for_the_requested_bar(): void
    {
        $membership = $this->setupBarMembership();
        $firstCocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Negroni']);
        $secondCocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Martini']);
        Cocktail::factory()->create(['name' => 'Foreign cocktail']);
        $repository = new EloquentCocktailMatchRepository();

        $matches = $repository->findManyByBarId(new BarId($membership->bar_id));

        $this->assertCount(2, $matches);
        $this->assertEqualsCanonicalizing(
            [$firstCocktail->id, $secondCocktail->id],
            array_map(static fn ($match): int => $match->getId()->value, $matches),
        );
        $this->assertEqualsCanonicalizing(
            ['Negroni', 'Martini'],
            array_map(static fn ($match): string => $match->getName()->toString(), $matches),
        );
    }

    public function test_it_returns_empty_array_when_bar_has_no_cocktails(): void
    {
        $membership = $this->setupBarMembership();
        Cocktail::factory()->create();
        $repository = new EloquentCocktailMatchRepository();

        $matches = $repository->findManyByBarId(new BarId($membership->bar_id));

        $this->assertSame([], $matches);
    }
}
