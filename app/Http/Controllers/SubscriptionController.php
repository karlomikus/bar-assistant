<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Laravel\Paddle\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Http\Resources\SubscriptionResource;

class SubscriptionController extends Controller
{
    public function subscription(Request $request): JsonResponse
    {
        if (!config('bar-assistant.enable_billing')) {
            abort(404);
        }

        $user = $request->user();
        $customer = $user->customer;

        // Customer missing locally, check paddle API
        if (!$customer) {
            $customers = Cashier::api('GET', 'customers', ['search' => $user->paddleEmail()]);
            $customerResponse = $customers['data'][0] ?? null;

            if ($customerResponse) {
                /** @var \Laravel\Paddle\Customer */
                $customer = $user->customer()->make();
                $customer->paddle_id = $customerResponse['id'];
                $customer->name = $customerResponse['name'];
                $customer->email = $customerResponse['email'];
                $customer->trial_ends_at = null;
                $customer->save();
            } else {
                $customer = $user->createAsCustomer();
            }
        }

        $sub = $user->subscription();

        return response()->json([
            'data' => [
                'prices' => config('bar-assistant.prices'),
                'customer' => [
                    'paddle_id' => $customer->paddle_id ?? null,
                    'paddle_email' => $user->paddleEmail(),
                    'paddle_name' => $user->paddleName(),
                ],
                'subscription' => $sub ? new SubscriptionResource($sub) : null,
            ]
        ]);
    }

    public function updateSubscription(Request $request): JsonResponse
    {
        if (!config('bar-assistant.enable_billing')) {
            abort(404);
        }

        $type = $request->post('type');

        if ($type === 'resume') {
            try {
                $request->user()->subscription()->resume();
            } catch (Throwable $e) {
                return response()->json($e->getMessage());
            }

            return response()->json(status: 204);
        }

        if ($type === 'pause') {
            try {
                $request->user()->subscription()->pause();
            } catch (Throwable $e) {
                return response()->json($e->getMessage());
            }

            return response()->json(status: 204);
        }

        abort(400);
    }
}
