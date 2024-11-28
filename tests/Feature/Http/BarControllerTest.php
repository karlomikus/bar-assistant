<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Image;
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

    public function test_create_bar_with_image(): void
    {
        $image = Image::factory()->create();

        $response = $this->postJson('/api/bars', [
            'name' => 'Test bar name',
            'images' => [$image->id]
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Test bar name');
        $response->assertJsonCount(1, 'data.images');
    }

    public function test_update_bar_with_image(): void
    {
        $image = Image::factory()->create();

        $response = $this->putJson('/api/bars/3', [
            'name' => 'Updated bar',
            'images' => [$image->id]
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data.images');
    }

    public function test_bar_delete(): void
    {
        $response = $this->deleteJson('/api/bars/3');

        $response->assertNoContent();
    }

    public function test_show_bar_members(): void
    {
        $response = $this->getJson('/api/bars/3/memberships');

        $response->assertJsonCount(1, 'data');
    }

    public function test_show_bar_members_forbidden(): void
    {
        $response = $this->getJson('/api/bars/1/memberships');

        $response->assertForbidden();
    }

    public function test_leave_bar(): void
    {
        $this->assertSame(1, Bar::find(3)->memberships()->count());
        $response = $this->deleteJson('/api/bars/3/memberships');

        $response->assertNoContent();
        $this->assertSame(0, Bar::find(3)->memberships()->count());
    }

    public function test_remove_member_from_bar(): void
    {
        $memberToRemove = User::factory()->create();
        $bar = Bar::find(3);
        $memberToRemove->joinBarAs($bar);

        $this->assertSame(2, $bar->memberships()->count());

        $response = $this->deleteJson('/api/bars/3/memberships/' . $memberToRemove->id);

        $response->assertNoContent();
        $this->assertSame(1, $bar->memberships()->count());
    }

    public function test_join_bar_with_invite_code(): void
    {
        $bar = Bar::factory()->create(['invite_code' => '01H8S3VH2HTEB3D893AW8NTBBC']);

        $response = $this->postJson('/api/bars/join', [
            'invite_code' => '01H8S3VH2HTEB3D893AW8NTBBC'
        ]);

        $response->assertOk();
        $this->assertSame(1, $bar->memberships()->count());
    }
}
