<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\UserRoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BarControllerTest extends TestCase
{
    use RefreshDatabase;

    private Bar $currentUserBar;

    public function setUp(): void
    {
        parent::setUp();

        $currentUser = User::factory()->create();
        $this->actingAs($currentUser);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $bar1 = Bar::factory()->create();
        $bar2 = Bar::factory()->create();
        $userBar = Bar::factory()->create([
            'created_user_id' => $currentUser->id
        ]);

        $user1->joinBarAs($bar1);
        $user2->joinBarAs($bar2);
        $currentUser->joinBarAs($userBar, UserRoleEnum::Admin);
        $this->currentUserBar = $userBar;
    }

    public function test_show_user_bars(): void
    {
        $response = $this->getJson('/api/bars');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_show_my_bar(): void
    {
        $response = $this->getJson('/api/bars/3');

        $response->assertOk();
    }

    public function test_dont_show_other_bar(): void
    {
        $response = $this->getJson('/api/bars/1');

        $response->assertForbidden();
    }

    public function test_create_bar(): void
    {
        $response = $this->postJson('/api/bars', [
            'name' => 'Test bar name'
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Test bar name');
        $response->assertJsonPath('data.slug', 'test-bar-name');
    }

    public function test_create_bar_with_slug(): void
    {
        $response = $this->postJson('/api/bars', [
            'name' => 'Test bar name',
            'slug' => 'my-custom sluggerino'
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Test bar name');
        $response->assertJsonPath('data.slug', 'my-custom-sluggerino');
    }

    public function test_transfer_my_bar_ownership(): void
    {
        $newOwner = User::factory()->create();

        $response = $this->postJson('/api/bars/3/transfer', [
            'user_id' => $newOwner->id,
        ]);

        $response->assertSuccessful();

        $bar = Bar::find(3);
        $this->assertSame($newOwner->id, $bar->created_user_id);
    }

    public function test_transfer_other_bar_ownership(): void
    {
        $newOwner = User::factory()->create();

        $response = $this->postJson('/api/bars/1/transfer', [
            'user_id' => $newOwner->id,
        ]);

        $response->assertForbidden();

        $bar = Bar::find(1);
        $this->assertNotSame($newOwner->id, $bar->created_user_id);
    }

    public function test_update_bar(): void
    {
        $response = $this->putJson('/api/bars/3', [
            'name' => 'Updated bar',
            'description' => 'description text',
            'default_units' => 'oz',
        ]);

        $response->assertOk();

        $bar = Bar::find(3);
        $this->assertSame(['default_units' => 'oz'], $bar->settings);
        $this->assertSame('Updated bar', $bar->name);
        $this->assertSame('description text', $bar->description);
    }
}
