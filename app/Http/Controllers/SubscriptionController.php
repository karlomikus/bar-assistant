<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Http\Resources\SubscriptionResource;

class SubscriptionController extends Controller
{
    public function subscription(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            $customer = $user->createAsCustomer();
        }

        $sub = $user->subscription();

        return response()->json([
            'data' => [
                'prices' => ['pri_01hfadsm6r4n2ga0x3d2gj5h51', 'pri_01hg60tgkgcy440vd7j8m40mb4'],
                'customer' => [
                    'paddle_id' => $customer->paddle_id,
                    'paddle_email' => $user->paddleName(),
                    'paddle_name' => $user->paddleEmail(),
                ],
                'subscription' => $sub ? new SubscriptionResource($sub) : null,
            ]
        ]);
    }

    public function updateSubscription(Request $request): JsonResponse
    {
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
