<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_list_users_response()
    {
        $this->actingAs(
            User::factory()->create()
        );

        User::factory()->count(10)->create();

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10 + 1)
                ->etc()
        );
    }

    public function test_list_users_no_access_response()
    {
        $this->actingAs(
            User::factory()->create(['is_admin' => false])
        );

        User::factory()->count(2)->create();

        $response = $this->getJson('/api/users');

        $response->assertForbidden();
    }

    public function test_show_user_response()
    {
        $this->actingAs(
            User::factory()->create()
        );

        $model = User::factory()->create([
            'name' => 'Test'
        ]);

        $response = $this->getJson('/api/users/' . $model->id);

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

    public function test_create_user_response()
    {
        $this->actingAs(
            User::factory()->create()
        );

        $response = $this->postJson('/api/users/', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'TEST',
            'is_admin' => false,
        ]);

        $response->assertCreated();
        $this->assertNotEmpty($response->headers->get('Location'));
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->has('data.search_api_key')
                ->where('data.name', 'Test')
                ->where('data.email', 'test@test.com')
                ->where('data.is_admin', false)
                ->etc()
        );
    }

    public function test_update_user_response()
    {
        $this->actingAs(
            User::factory()->create()
        );

        $model = User::factory()->create([
            'name' => 'Initial Name',
        ]);

        $response = $this->putJson('/api/users/' . $model->id, [
            'name' => 'Updated Name',
            'email' => 'test@test.com',
            'is_admin' => true,
        ]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $model->id)
                ->where('data.name', 'Updated Name')
                ->where('data.email', 'test@test.com')
                ->where('data.is_admin', true)
                ->etc()
        );
    }

    public function test_delete_user_response()
    {
        $this->actingAs(
            User::factory()->create()
        );

        $model = User::factory()->create([
            'name' => 'Initial Name',
        ]);

        $response = $this->delete('/api/users/' . $model->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $model->id]);
    }
}
