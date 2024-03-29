<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\SubscriptionChanged;
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

        if (!$customer) {
            $customer = $user->createAsCustomer();
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

                Mail::to($request->user())->queue(new SubscriptionChanged($type));
            } catch (Throwable $e) {
                abort(400, $e->getMessage());
            }

            return response()->json(status: 204);
        }

        if ($type === 'pause') {
            try {
                $request->user()->subscription()->pause();

                Mail::to($request->user())->queue(new SubscriptionChanged($type));
            } catch (Throwable $e) {
                abort(400, $e->getMessage());
            }

            return response()->json(status: 204);
        }

        abort(400);
    }
}
