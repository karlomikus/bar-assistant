<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\Config;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BarControllerTest extends TestCase
{
    use RefreshDatabase;

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
        $response->assertHeader('Location');
    }

    public function test_create_bar_with_slug(): void
    {
        $response = $this->postJson('/api/bars', [
            'name' => 'Test bar name',
            'slug' => 'my-custom sluggerino'
        ]);

        $response->assertCreated();
        $response->assertHeader('Location');
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

        $response->assertNoContent();
    }

    public function test_bar_delete(): void
    {
        $response = $this->deleteJson('/api/bars/3');

        $response->assertNoContent();
    }

    public function test_join_bar_with_invite_code(): void
    {
        $bar = Bar::factory()->create(['invite_code' => '01H8S3VH2HTEB3D893AW8NTBBC']);

        $response = $this->postJson('/api/bars/join', [
            'invite_code' => '01H8S3VH2HTEB3D893AW8NTBBC'
        ]);

        $response->assertNoContent();
        $this->assertSame(1, $bar->memberships()->count());
    }

    public function test_limits_bar_count_for_unsubscribed_users(): void
    {
        Config::set('bar-assistant.enable_billing', true);

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/bars', [
            'name' => 'Test bar name'
        ]);

        $response->assertCreated();

        $user->refresh();

        $response = $this->postJson('/api/bars', [
            'name' => 'Test bar name'
        ]);

        $response->assertForbidden();
    }

    public function test_not_found_resources(): void
    {
        $this->getJson('/api/bars/999')->assertNotFound();
        $this->putJson('/api/bars/999', ['name' => 'Test'])->assertNotFound();
        $this->deleteJson('/api/bars/999')->assertNotFound();
        $this->postJson('/api/bars/999/transfer', ['user_id' => 1])->assertNotFound();
    }

    public function test_invalid_input_unprocessable(): void
    {
        $bar = Bar::factory()->create();
        $this->postJson('/api/bars', [])->assertUnprocessable();
        $this->putJson('/api/bars/' . $bar->id, ['name' => ''])->assertUnprocessable();
    }
}
