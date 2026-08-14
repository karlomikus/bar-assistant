<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use DateTimeImmutable;
use Laravel\Paddle\Cashier;
use Kami\Cocktail\Models\User;
use Laravel\Paddle\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\AccountDeleted;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Illuminate\Testing\Fluent\AssertableJson;
use Kami\Cocktail\Models\Enums\BarStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_user_response(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/profile');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $user->id)
                ->where('data.email', $user->email)
                ->where('data.name', $user->name)
                ->etc()
        );
    }

    public function test_update_current_user_response(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/profile', [
            'email' => 'new@example.com',
            'name' => 'Test Guy',
        ]);

        $response->assertNoContent();
    }

    public function test_update_current_user_with_password_response(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('my-test-password'),
        ]);
        $this->actingAs($user);

        $oldPassword = $user->password;
        $response = $this->postJson('/api/profile/change-password', [
            'current_password' => 'my-test-password',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertNoContent();
        $user->refresh();

        $this->assertNotSame($oldPassword, $user->password);
    }

    public function test_update_current_user_with_password_fail_response(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('my-test-password'),
        ]);
        $this->actingAs($user);

        $response = $this->postJson('/api/profile/change-password', [
            'current_password' => 'my-test-password',
            'password' => '12345',
            'password_confirmation' => '123451',
        ]);

        $response->assertUnprocessable();
    }

    public function test_update_current_user_with_email_fail_response(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/profile', [
            'email' => 'newexample.com',
            'name' => 'Test Guy',
        ]);

        $response->assertUnprocessable();
    }

    public function test_update_current_user_settings_response(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/profile', [
            'email' => 'new@example.com',
            'name' => 'Test Guy',
            'settings' => [
                'language' => 'en',
                'theme' => 'dark',
                'unsupported' => 'test',
            ],
        ]);

        $response->assertNoContent();
    }

    public function test_delete_profile_response(): void
    {
        $membership = $this->setupBarMembership();
        $user = $membership->user;

        Mail::fake();

        Cashier::fake([
            'customers*' => [
                'data' => [[
                    'id' => 'ctm_12345',
                    'name' => $user->name,
                    'email' => $user->email,
                ]],
            ],
            'subscriptions*' => [
                'data' => [
                    'status' => 'active',
                    'canceled_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                    'management_urls' => [
                        'update_payment_method' => 'https://localhost/test-update',
                        'cancel' => 'https://localhost/test-cancel',
                    ],
                ],
            ],
            'transactions*' => [
                'data' => [
                    'url' => 'https://localhost/pdf-test',
                ]
            ]
        ]);
        $user->createAsCustomer();

        $user->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_12345',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseCount('subscriptions', 1);
        DB::table('bar_memberships')->insert(['bar_id' => 1, 'user_id' => $user->id, 'user_role_id' => UserRoleEnum::General->value]);
        DB::table('oauth_credentials')->insert(['user_id' => $user->id, 'provider' => 'github', 'provider_id' => 1]);

        $this->actingAs($user);

        $response = $this->delete('/api/profile');

        $response->assertNoContent();

        Mail::assertQueued(AccountDeleted::class);

        $this->assertDatabaseMissing('bar_memberships', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('oauth_credentials', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('subscriptions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('bars', ['created_user_id' => $user->id, 'status' => BarStatusEnum::Deactivated->value]);
        $anonUser = DB::table('users')->find($user->id);
        $this->assertSame('Deleted User', $anonUser->name);
        $this->assertSame('deleted', $anonUser->password);
        $this->assertTrue(str_starts_with((string) $anonUser->email, 'userdeleted'));
        $this->assertNull($anonUser->email_verified_at);
    }

    public function test_unauthenticated_access_policy(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized();
        $this->postJson('/api/profile')->assertUnauthorized();
        $this->postJson('/api/profile/change-password')->assertUnauthorized();
        $this->deleteJson('/api/profile')->assertUnauthorized();
        $this->deleteJson('/api/profile/sso/github')->assertUnauthorized();
    }
}
