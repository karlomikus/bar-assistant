<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Laravel\Paddle\Transaction;
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
            'next_billed_at' => $this->nextPayment(),
            'update_payment_url' => $this->paymentMethodUpdateUrl(),
            'cancel_url' => $this->cancelUrl(),
            'transactions' => $this->transactions->map(function ($model): array {
                /** @var Transaction */
                $tx = $model;

                $invoiceUrl = null;
                try {
                    $invoiceUrl = $tx->invoicePdf();
                } catch (\Throwable) {
                }

                return [
                    'total' => $tx->total,
                    'tax' => $tx->tax,
                    'currency' => $tx->currency,
                    'status' => $tx->status,
                    'invoice_number' => $tx->invoice_number,
                    'url' => $invoiceUrl,
                    'billed_at' => $tx->billed_at,
                    'created_at' => $tx->created_at,
                    'updated_at' => $tx->updated_at,
                ];
            })
        ];
    }
}
