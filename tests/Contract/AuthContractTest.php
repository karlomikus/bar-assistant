<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\ContractTestCase;
use Laravel\Sanctum\Sanctum;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthContractTest extends ContractTestCase
{
    use RefreshDatabase;

    public function test_contract_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'my-test-password'
        ]);

        $response->assertValidResponse(200);
    }

    public function test_contract_logout(): void
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->postJson('/api/logout');

        $response->assertValidResponse(204);
    }

    public function test_contract_register_response_200(): void
    {
        $response = $this->postJson('/api/register', [
            'email' => 'test@test.com',
            'password' => 'test-password',
            'name' => 'Test Guy',
        ]);

        $response
            ->assertValidRequest()
            ->assertValidResponse(201);
    }

    public function test_contract_register_response_422(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test Guy',
        ]);

        $response->assertValidResponse(422);
    }
}
