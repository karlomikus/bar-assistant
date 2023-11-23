<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\UserRoleEnum;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsersControllerTest extends TestCase
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

        $response = $this->getJson('/api/users?bar_id=1');

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

        $response = $this->getJson('/api/users/' . $user->id . '?bar_id=1');

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
        $response = $this->postJson('/api/users?bar_id=1', [
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

        $response = $this->putJson('/api/users/' . $user->id . '?bar_id=1', [
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

    public function test_delete_user_response(): void
    {
        $user = User::factory()->create([
            'name' => 'Initial Name',
        ]);
        DB::table('bar_memberships')->insert(['bar_id' => 1, 'user_id' => $user->id, 'user_role_id' => UserRoleEnum::General->value]);

        $this->actingAs($user);

        $response = $this->delete('/api/users/' . $user->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('bar_memberships', ['user_id' => $user->id]);
        $anonUser = DB::table('users')->find($user->id);
        $this->assertSame('Deleted User', $anonUser->name);
        $this->assertSame('deleted', $anonUser->password);
        $this->assertTrue(str_starts_with($anonUser->email, 'userdeleted'));
        $this->assertNull($anonUser->email_verified_at);
        $this->assertNull($anonUser->remember_token);
    }
}
