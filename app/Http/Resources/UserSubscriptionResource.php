<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\User
 */
#[OAT\Schema(
    schema: 'UserSubscription',
    description: 'User subscription resource',
    properties: [
        new OAT\Property(property: 'prices', type: 'array', items: new OAT\Items(type: 'string')),
        new OAT\Property(property: 'customer', type: 'object', properties: [
            new OAT\Property(property: 'paddle_id', type: 'string', nullable: true),
            new OAT\Property(property: 'paddle_email', type: 'string', nullable: true),
            new OAT\Property(property: 'paddle_name', type: 'string', nullable: true),
        ], required: ['paddle_id', 'paddle_email', 'paddle_name']),
        new OAT\Property(property: 'subscription', type: SubscriptionResource::class, nullable: true),
    ],
    required: ['prices', 'customer', 'subscription']
)]
class UserSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $customer = $this->customer;
        $subscription = $this->subscription();

        return [
            'prices' => config('bar-assistant.prices'),
            'customer' => [
                'paddle_id' => $customer->paddle_id ?? null,
                'paddle_email' => $this->paddleEmail(),
                'paddle_name' => $this->paddleName(),
            ],
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
        ];
    }
}
