<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use DateTimeImmutable;
use Laravel\Paddle\Cashier;
use Kami\Cocktail\Models\User;
use Laravel\Paddle\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
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
            'new_password' => '12345',
            'new_password_confirmation' => '12345',
        ]);

        $response->assertNoContent();
        $user->refresh();

        $this->assertNotSame($oldPassword, $user->password);
        $this->assertTrue(Hash::check('12345', $user->password));
    }

    public function test_change_password_sets_the_new_password_not_an_empty_string(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('my-test-password'),
        ]);
        $this->actingAs($user);

        $this->postJson('/api/profile/change-password', [
            'current_password' => 'my-test-password',
            'new_password' => 'my-new-password',
            'new_password_confirmation' => 'my-new-password',
        ])->assertNoContent();

        $user->refresh();

        // Regression: the request body was previously parsed from the
        // "password" field, hashing an empty string instead of the new password
        $this->assertTrue(Hash::check('my-new-password', $user->password));
        $this->assertFalse(Hash::check('', $user->password));
    }

    public function test_change_password_requires_new_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('my-test-password'),
        ]);
        $this->actingAs($user);

        $response = $this->postJson('/api/profile/change-password', [
            'current_password' => 'my-test-password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_change_password_with_wrong_current_password_response(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('my-test-password'),
        ]);
        $this->actingAs($user);

        $response = $this->postJson('/api/profile/change-password', [
            'current_password' => 'wrong-password',
            'new_password' => '12345',
            'new_password_confirmation' => '12345',
        ]);

        $response->assertForbidden();
    }

    public function test_change_password_preserves_email_verification(): void
    {
        Config::set('bar-assistant.mail_require_confirmation', true);

        $user = User::factory()->create([
            'password' => Hash::make('my-test-password'),
        ]);
        $this->actingAs($user);

        $this->postJson('/api/profile/change-password', [
            'current_password' => 'my-test-password',
            'new_password' => '12345',
            'new_password_confirmation' => '12345',
        ])->assertNoContent();

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        Auth::forgetGuards();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => '12345',
        ])->assertOk();
    }

    public function test_update_profile_preserves_email_verification(): void
    {
        Config::set('bar-assistant.mail_require_confirmation', true);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/profile', [
            'email' => $user->email,
            'name' => 'New Name',
        ])->assertNoContent();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_update_current_user_with_password_fail_response(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('my-test-password'),
        ]);
        $this->actingAs($user);

        $response = $this->postJson('/api/profile/change-password', [
            'current_password' => 'my-test-password',
            'new_password' => '12345',
            'new_password_confirmation' => '123451',
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
                    'canceled_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
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

    public function test_delete_profile_revokes_all_access_tokens(): void
    {
        Mail::fake();

        $membership = $this->setupBarMembership();
        $user = $membership->user;
        $bar = $membership->bar;

        $loginToken = $user->createToken('login');
        $personalToken = $user->createToken('personal', ['bars.write']);

        $this->withToken($loginToken->plainTextToken)
            ->deleteJson('/api/profile')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);

        // Simulate a new request so the auth guard does not reuse the previous user
        Auth::forgetGuards();

        $this->withToken($loginToken->plainTextToken)
            ->getJson('/api/profile')
            ->assertUnauthorized();

        $this->withToken($personalToken->plainTextToken)
            ->putJson('/api/bars/' . $bar->id, ['name' => 'Hijacked bar'])
            ->assertUnauthorized();

        $this->assertDatabaseHas('bars', [
            'id' => $bar->id,
            'name' => $bar->name,
            'status' => BarStatusEnum::Deactivated->value,
        ]);
    }

    public function test_anonymized_user_cannot_authenticate_even_with_a_valid_token(): void
    {
        Mail::fake();

        $membership = $this->setupBarMembership();
        $user = $membership->user;

        $token = $user->createToken('login');

        $this->withToken($token->plainTextToken)
            ->deleteJson('/api/profile')
            ->assertNoContent();

        Auth::forgetGuards();

        $anonymizedUser = User::findOrFail($user->id);
        $ghostToken = $anonymizedUser->createToken('ghost');

        $this->withToken($ghostToken->plainTextToken)
            ->getJson('/api/profile')
            ->assertUnauthorized();

        $this->withToken($ghostToken->plainTextToken)
            ->postJson('/api/profile', ['email' => 'ghost@example.com', 'name' => 'Ghost'])
            ->assertUnauthorized();
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
