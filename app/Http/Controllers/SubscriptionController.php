<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\SubscriptionChanged;
use Kami\Cocktail\Http\Resources\UserSubscriptionResource;

class SubscriptionController extends Controller
{
    #[OAT\Get(path: '/billing/subscription', tags: ['Billing'], operationId: 'showSubscription', description: 'Show a subscription status', summary: 'Show subscription')]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(UserSubscriptionResource::class),
    ])]
    #[BAO\NotFoundResponse]
    public function subscription(Request $request): UserSubscriptionResource
    {
        if (!config('bar-assistant.enable_billing')) {
            abort(404);
        }

        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            $customer = $user->createAsCustomer();
        }

        return new UserSubscriptionResource($user);
    }

    #[OAT\Post(path: '/billing/subscription', tags: ['Billing'], operationId: 'updateSubscription', description: 'Update user billing subscription', summary: 'Update subscription', requestBody: new OAT\RequestBody(
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
