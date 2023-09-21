<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\ContractTestCase;
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
}
