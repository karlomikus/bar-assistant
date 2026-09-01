<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\BarMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicCocktailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_public_cocktails_by_strength_rating_and_tags(): void
    {
        $bar = Bar::factory()->create(['id' => random_int(100000, 999999), 'is_public' => true]);
        $membership = BarMembership::factory()->for($bar)->for(User::factory())->create();
        $citrus = Tag::factory()->for($bar)->create(['name' => 'Citrus']);
        $spirit = Tag::factory()->for($bar)->create(['name' => 'Spirit']);
        $inRange = Cocktail::factory()->for($bar)->create(['name' => 'In range', 'abv' => 18]);
        $high = Cocktail::factory()->for($bar)->create(['name' => 'High', 'abv' => 28]);
        $unrated = Cocktail::factory()->for($bar)->create(['name' => 'Unrated', 'abv' => 20]);
        Cocktail::factory()->for($bar)->create(['name' => 'No ABV', 'abv' => null]);
        $otherBar = Bar::factory()->create(['is_public' => true]);
        $otherCocktail = Cocktail::factory()->for($otherBar)->create(['name' => 'Other bar', 'abv' => 20]);
        $inRange->tags()->attach($citrus);
        $high->tags()->attach($spirit);
        $unrated->tags()->attach($citrus);
        $otherCocktail->tags()->attach($citrus);
        $inRange->rate(3.5, $membership->id);
        $high->rate(4, $membership->id);

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[average_rating_min]=3.5")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[abv_min]=18&filter[abv_max]=28")
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[tag_id]={$citrus->id},{$spirit->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[abv_min]=18&filter[abv_max]=28&filter[average_rating_min]=3.5&filter[tag_id]={$citrus->id},{$spirit->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => $inRange->name])
            ->assertJsonFragment(['name' => $high->name])
            ->assertJsonMissing(['name' => 'Unrated'])
            ->assertJsonMissing(['name' => 'Other bar']);

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[abv_max]=2")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[tag_id]={$citrus->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/public/bars/{$bar->id}/cocktails")
            ->assertOk()
            ->assertJsonCount(4, 'data');
    }

    public function test_it_returns_complete_bar_scoped_tag_metadata(): void
    {
        $bar = Bar::factory()->create(['id' => random_int(100000, 999999), 'is_public' => true]);
        $otherBar = Bar::factory()->create(['is_public' => true]);
        $alpha = Tag::factory()->for($bar)->create(['name' => 'Alpha']);
        $zulu = Tag::factory()->for($bar)->create(['name' => 'Zulu']);
        Tag::factory()->for($bar)->create(['name' => 'Unused']);
        $private = Tag::factory()->for($otherBar)->create(['name' => 'Private']);
        $first = Cocktail::factory()->for($bar)->create();
        $second = Cocktail::factory()->for($bar)->create();
        $otherCocktail = Cocktail::factory()->for($otherBar)->create();
        $first->tags()->attach($zulu);
        $second->tags()->attach($alpha);
        $second->tags()->attach($zulu);
        $otherCocktail->tags()->attach($private);

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[tag_id]={$zulu->id}")
            ->assertOk()
            ->assertJsonPath('meta.filters.tags', [
                ['id' => $alpha->id, 'name' => 'Alpha'],
                ['id' => $zulu->id, 'name' => 'Zulu'],
            ]);
    }
}
