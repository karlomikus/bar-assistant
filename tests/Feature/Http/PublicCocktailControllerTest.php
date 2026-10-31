<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\Models\CocktailIngredient;
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

    public function test_it_filters_public_cocktails_by_glass_method_and_ingredient_count(): void
    {
        $bar = Bar::factory()->create(['id' => random_int(100000, 999999), 'is_public' => true]);
        $coupe = Glass::factory()->for($bar)->create(['name' => 'Coupe']);
        $highball = Glass::factory()->for($bar)->create(['name' => 'Highball']);
        $shaken = CocktailMethod::factory()->for($bar)->create(['name' => 'Shaken']);
        $stirred = CocktailMethod::factory()->for($bar)->create(['name' => 'Stirred']);
        $otherBar = Bar::factory()->create(['is_public' => true]);
        $foreignGlass = Glass::factory()->for($otherBar)->create(['name' => 'Foreign glass']);
        $foreignMethod = CocktailMethod::factory()->for($otherBar)->create(['name' => 'Foreign method']);
        $inCoupe = Cocktail::factory()->for($bar)->create(['name' => 'In coupe', 'glass_id' => $coupe->id, 'cocktail_method_id' => $shaken->id]);
        $bigHighball = Cocktail::factory()->for($bar)->create(['name' => 'Big highball', 'glass_id' => $highball->id, 'cocktail_method_id' => $stirred->id]);
        Cocktail::factory()->for($bar)->create(['name' => 'No method', 'glass_id' => $coupe->id, 'cocktail_method_id' => null]);
        Cocktail::factory()->for($otherBar)->create(['name' => 'Other bar', 'glass_id' => $foreignGlass->id, 'cocktail_method_id' => $foreignMethod->id]);
        CocktailIngredient::factory()->for($inCoupe, 'cocktail')->count(2)->create();
        CocktailIngredient::factory()->for($bigHighball, 'cocktail')->count(4)->create();

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[glass_id]={$coupe->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[glass_id]={$coupe->id},{$highball->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[cocktail_method_id]={$shaken->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'In coupe']);

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[cocktail_method_id]={$shaken->id},{$stirred->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['name' => 'No method']);

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[total_ingredients]=4")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Big highball']);

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[glass_id]={$highball->id}&filter[total_ingredients]=2")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Big highball']);

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[glass_id]={$foreignGlass->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[cocktail_method_id]={$foreignMethod->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/public/bars/{$bar->id}/cocktails")
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $queryParams = ['filter' => ['glass_id' => (string) $coupe->id]];
        ksort($queryParams);
        $cacheKey = 'public_cocktails_index:' . $bar->id . ':' . sha1(http_build_query($queryParams));
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_it_returns_complete_bar_scoped_glass_and_method_metadata(): void
    {
        $bar = Bar::factory()->create(['id' => random_int(100000, 999999), 'is_public' => true]);
        $otherBar = Bar::factory()->create(['is_public' => true]);
        $coupe = Glass::factory()->for($bar)->create(['name' => 'Coupe']);
        $alpha = Glass::factory()->for($bar)->create(['name' => 'Alpha glass']);
        Glass::factory()->for($bar)->create(['name' => 'Unused glass']);
        $foreignGlass = Glass::factory()->for($otherBar)->create(['name' => 'Foreign glass']);
        $stirred = CocktailMethod::factory()->for($bar)->create(['name' => 'Stirred']);
        $built = CocktailMethod::factory()->for($bar)->create(['name' => 'Built']);
        $foreignMethod = CocktailMethod::factory()->for($otherBar)->create(['name' => 'Foreign method']);
        Cocktail::factory()->for($bar)->create(['glass_id' => $coupe->id, 'cocktail_method_id' => $stirred->id]);
        Cocktail::factory()->for($bar)->create(['glass_id' => $alpha->id, 'cocktail_method_id' => $built->id]);
        Cocktail::factory()->for($bar)->create(['glass_id' => $coupe->id, 'cocktail_method_id' => null]);
        Cocktail::factory()->for($otherBar)->create(['glass_id' => $foreignGlass->id, 'cocktail_method_id' => $foreignMethod->id]);

        $this->getJson("/api/public/bars/{$bar->id}/cocktails?filter[glass_id]={$coupe->id}")
            ->assertOk()
            ->assertJsonPath('meta.filters.glasses', [
                ['id' => $alpha->id, 'name' => 'Alpha glass'],
                ['id' => $coupe->id, 'name' => 'Coupe'],
            ])
            ->assertJsonPath('meta.filters.methods', [
                ['id' => $built->id, 'name' => 'Built'],
                ['id' => $stirred->id, 'name' => 'Stirred'],
            ]);
    }
}
