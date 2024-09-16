<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\SubscriptionChanged;
use Kami\Cocktail\Http\Resources\SubscriptionResource;

class SubscriptionController extends Controller
{
    #[OAT\Get(path: '/billing/subscriptions', tags: ['Billing'], summary: 'Get subscription status')]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\UserSubscription::class),
    ])]
    #[BAO\NotFoundResponse]
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

    #[OAT\Post(path: '/billing/subscriptions', tags: ['Billing'], summary: 'Update subscription', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', required: ['type'], properties: [
                new OAT\Property(property: 'type', type: 'string'),
            ]),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
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
