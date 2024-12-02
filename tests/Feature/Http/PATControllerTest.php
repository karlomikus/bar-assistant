<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Carbon\Carbon;
use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Enums\AbilityEnum;
use Illuminate\Testing\Fluent\AssertableJson;
use Kami\Cocktail\Models\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PATControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_tokens(): void
    {
        $anotherUser = User::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        PersonalAccessToken::factory()->for($anotherUser, 'tokenable')->count(10)->create();
        PersonalAccessToken::factory()->for($user, 'tokenable')->count(5)->create();

        $response = $this->getJson('/api/tokens');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 5)
                ->etc()
        );
    }

    public function test_create_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/tokens', [
            'name' => 'My new token',
            'abilities' => [AbilityEnum::CocktailsRead->value, AbilityEnum::IngredientsWrite->value],
            'expires_at' => Carbon::now()->addMonth()->toAtomString(),
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json->has('data.token')
        );
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'My new token', 'abilities' => json_encode([AbilityEnum::CocktailsRead->value, AbilityEnum::IngredientsWrite->value])]);
    }

    public function test_delete_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $token = PersonalAccessToken::factory()->for($user, 'tokenable')->create();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->id]);

        $response = $this->deleteJson('/api/tokens/' . $token->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }

    public function test_token_ability(): void
    {
        $barMembership = $this->setupBarMembership();
        $token = $barMembership->user->createToken(
            'My new token',
            [AbilityEnum::IngredientsRead->value],
            Carbon::now()->addMonth()
        );

        $response = $this->getJson('/api/cocktails', ['Authorization' => 'Bearer ' . $token->plainTextToken, 'Bar-Assistant-Bar-Id' => $barMembership->bar->id]);
        $response->assertForbidden();

        $response = $this->getJson('/api/ingredient-categories', ['Authorization' => 'Bearer ' . $token->plainTextToken, 'Bar-Assistant-Bar-Id' => $barMembership->bar->id]);
        $response->assertForbidden();

        $response = $this->getJson('/api/ingredients', ['Authorization' => 'Bearer ' . $token->plainTextToken, 'Bar-Assistant-Bar-Id' => $barMembership->bar->id]);
        $response->assertSuccessful();
    }
}
