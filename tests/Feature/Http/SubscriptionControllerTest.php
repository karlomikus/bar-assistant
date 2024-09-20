<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Carbon\Carbon;
use Tests\TestCase;
use Laravel\Paddle\Cashier;
use Kami\Cocktail\Models\User;
use Laravel\Paddle\Subscription;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Kami\Cocktail\Mail\SubscriptionChanged;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Config::set('bar-assistant.enable_billing', true);

        $user = User::factory()->create();
        Cashier::fake([
            'customers*' => [
                'data' => [[
                    'id' => 'ctm_12345',
                    'name' => $user->name,
                    'email' => $user->email,
                ]],
            ],
        ]);
        $user->createAsCustomer();

        $this->actingAs($user);
    }

    public function test_no_subscription_response(): void
    {
        /** @var \Kami\Cocktail\Models\User */
        $user = auth('sanctum')->user();

        $response = $this->getJson('/api/billing/subscription');
        $response->assertSuccessful();

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.prices.0', 'pri_12345')
                ->where('data.prices.1', 'pri_67890')
                ->where('data.customer.paddle_id', 'ctm_12345')
                ->where('data.customer.paddle_email', $user->email)
                ->where('data.customer.paddle_name', $user->name)
                ->where('data.subscription', null)
                ->etc()
        );
    }

    public function test_subscription_active_response(): void
    {
        /** @var \Kami\Cocktail\Models\User */
        $user = auth('sanctum')->user();

        Cashier::fake([
            'subscriptions*' => [
                'data' => [
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

        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_12345',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $subscription->items()->create([
            'subscription_id' => 1,
            'product_id' => 'pro_12345',
            'price_id' => 'pri_12345',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $user->transactions()->create([
            'paddle_id' => 'txn_12345',
            'paddle_subscription_id' => 'sub_12345',
            'invoice_number' => '1000-10001',
            'status' => 'completed',
            'total' => '150',
            'tax' => '50',
            'currency' => 'EUR',
            'billed_at' => now(),
        ]);

        $response = $this->getJson('/api/billing/subscription');
        $response->assertSuccessful();

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.prices.0', 'pri_12345')
                ->where('data.prices.1', 'pri_67890')
                ->where('data.customer.paddle_id', 'ctm_12345')
                ->where('data.customer.paddle_email', $user->email)
                ->where('data.customer.paddle_name', $user->name)
                ->where('data.subscription.type', 'default')
                ->where('data.subscription.status', 'active')
                ->where('data.subscription.paddle_id', 'sub_12345')
                ->where('data.subscription.paused_at', null)
                ->where('data.subscription.ends_at', null)
                ->where('data.subscription.is_recurring', true)
                ->where('data.subscription.past_due', false)
                ->where('data.subscription.update_payment_url', 'https://localhost/test-update')
                ->where('data.subscription.cancel_url', 'https://localhost/test-cancel')
                ->where('data.subscription.transactions.0.status', 'completed')
                ->where('data.subscription.transactions.0.tax', '50')
                ->where('data.subscription.transactions.0.currency', 'EUR')
                ->where('data.subscription.transactions.0.total', '150')
                ->where('data.subscription.transactions.0.invoice_number', '1000-10001')
                ->where('data.subscription.transactions.0.url', 'https://localhost/pdf-test')
                ->etc()
        );
    }

    public function test_subscription_pause_response(): void
    {
        Mail::fake();
        /** @var \Kami\Cocktail\Models\User */
        $user = auth('sanctum')->user();

        Cashier::fake([
            'subscriptions/sub_12345/pause' => [
                'data' => [
                    'status' => 'paused',
                    'scheduled_change' => [
                        'effective_at' => Carbon::now()->toDateTimeString()
                    ],
                    'paused_at' => now(),
                    'items' => [],
                ],
            ]
        ]);

        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_12345',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $subscription->items()->create([
            'subscription_id' => 1,
            'product_id' => 'pro_12345',
            'price_id' => 'pri_12345',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $response = $this->postJson('/api/billing/subscription', ['type' => 'pause']);
        $response->assertSuccessful();

        Mail::assertQueued(SubscriptionChanged::class);

        $this->assertDatabaseHas('subscriptions', ['paddle_id' => 'sub_12345', 'status' => Subscription::STATUS_PAUSED]);
    }

    public function test_subscription_resume_response(): void
    {
        Mail::fake();
        /** @var \Kami\Cocktail\Models\User */
        $user = auth('sanctum')->user();

        Cashier::fake([
            'subscriptions/sub_12345/resume' => [
                'data' => [
                    'status' => 'active',
                    'paused_at' => null,
                    'items' => [],
                ],
            ]
        ]);

        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_12345',
            'status' => Subscription::STATUS_PAUSED,
        ]);

        $subscription->items()->create([
            'subscription_id' => 1,
            'product_id' => 'pro_12345',
            'price_id' => 'pri_12345',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $response = $this->postJson('/api/billing/subscription', ['type' => 'resume']);
        $response->assertSuccessful();

        Mail::assertQueued(SubscriptionChanged::class);

        $this->assertDatabaseHas('subscriptions', ['paddle_id' => 'sub_12345', 'status' => Subscription::STATUS_ACTIVE]);
    }
}
