<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\ContractTestCase;
use Kami\Cocktail\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileContractTest extends ContractTestCase
{
    use RefreshDatabase;

    public function test_get_profile_200_response(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/profile');

        $response->assertValidResponse(200);
    }
}
