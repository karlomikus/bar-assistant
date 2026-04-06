<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MemberControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );

        $this->setupBar();
    }

    public function test_list_users_response(): void
    {
        $users = User::factory()->count(9)->create();
        foreach ($users as $user) {
            DB::table('bar_memberships')->insert(['bar_id' => 1, 'user_id' => $user->id, 'user_role_id' => UserRoleEnum::General->value]);
        }

        $this->withHeader('Bar-Assistant-Bar-Id', '1');
        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );
    }

    public function test_show_user_response(): void
    {
        $user = User::factory()->create([
            'name' => 'Test'
        ]);
        DB::table('bar_memberships')->insert(['bar_id' => 1, 'user_id' => $user->id, 'user_role_id' => UserRoleEnum::General->value]);

        $this->withHeader('Bar-Assistant-Bar-Id', '1');
        $response = $this->getJson('/api/users/' . $user->id);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->where('data.name', 'Test')
                ->etc()
        );
    }

    public function test_create_user_response(): void
    {
        $this->withHeader('Bar-Assistant-Bar-Id', '1');

        $response = $this->postJson('/api/users', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'TEST1',
            'role_id' => UserRoleEnum::Admin->value,
        ]);

        $response->assertCreated();
        $this->assertNotEmpty($response->headers->get('Location'));
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->where('data.name', 'Test')
                ->where('data.email', 'test@test.com')
                ->etc()
        );
    }

    public function test_update_user_response(): void
    {
        $user = User::factory()->create([
            'name' => 'Initial Name',
        ]);
        DB::table('bar_memberships')->insert(['bar_id' => 1, 'user_id' => $user->id, 'user_role_id' => UserRoleEnum::General->value]);

        $this->withHeader('Bar-Assistant-Bar-Id', '1');
        $response = $this->putJson('/api/users/' . $user->id, [
            'name' => 'Updated Name',
            'email' => 'test@test.com',
            'role_id' => UserRoleEnum::General->value,
        ]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $user->id)
                ->where('data.name', 'Updated Name')
                ->where('data.email', $user->email)
                ->etc()
        );
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
}
