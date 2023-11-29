<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Laravel\Paddle\Subscription
 */
class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'type' => $this->type,
            'paddle_id' => $this->paddle_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'paused_at' => $this->paused_at,
            'ends_at' => $this->ends_at,
            'past_due' => $this->pastDue(),
            'is_recurring' => $this->recurring(),
            'next_billed_at' => $this->nextBilledAt(),
            'update_payment_url' => $this->paymentMethodUpdateUrl(),
        ];
    }
}
