<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Laravel\Paddle\Transaction;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Laravel\Paddle\Subscription
 */
#[OAT\Schema(
    schema: 'Subscription',
    description: 'Subscription resource',
    properties: [
        new OAT\Property(property: 'type', type: 'string'),
        new OAT\Property(property: 'paddle_id', type: 'string'),
        new OAT\Property(property: 'status', type: 'string'),
        new OAT\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OAT\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OAT\Property(property: 'paused_at', type: 'string', format: 'date-time'),
        new OAT\Property(property: 'ends_at', type: 'string', format: 'date-time'),
        new OAT\Property(property: 'past_due', type: 'boolean'),
        new OAT\Property(property: 'is_recurring', type: 'boolean'),
        new OAT\Property(property: 'next_billed_at', type: 'object', properties: [
            new OAT\Property(property: 'currency', type: 'string'),
            new OAT\Property(property: 'amount', type: 'string'),
            new OAT\Property(property: 'date', type: 'string', format: 'date-time'),
        ], required: ['currency', 'amount', 'date'], nullable: true),
        new OAT\Property(property: 'update_payment_url', type: 'string', format: 'uri'),
        new OAT\Property(property: 'cancel_url', type: 'string', format: 'uri'),
        new OAT\Property(property: 'transactions', type: 'array', items: new OAT\Items(type: 'object', properties: [
            new OAT\Property(property: 'total', type: 'string'),
            new OAT\Property(property: 'tax', type: 'string'),
            new OAT\Property(property: 'currency', type: 'string'),
            new OAT\Property(property: 'status', type: 'string'),
            new OAT\Property(property: 'invoice_number', type: 'string'),
            new OAT\Property(property: 'url', type: 'string', format: 'uri'),
            new OAT\Property(property: 'billed_at', type: 'string', format: 'date-time'),
            new OAT\Property(property: 'created_at', type: 'string', format: 'date-time'),
            new OAT\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        ], required: ['total', 'tax', 'currency', 'status', 'invoice_number', 'url', 'billed_at', 'created_at', 'updated_at'])),
    ],
    required: ['type', 'paddle_id', 'status', 'created_at', 'updated_at', 'paused_at', 'ends_at', 'past_due', 'is_recurring', 'next_billed_at', 'update_payment_url', 'cancel_url'],
)]
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
