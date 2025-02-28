<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\OauthCredential
 */
#[OAT\Schema(
    schema: 'OauthCredential',
    description: 'OAuth Credential information',
    properties: [
        new OAT\Property(property: 'provider', ref: SSOProviderResource::class),
        new OAT\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OAT\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
    required: ['provider', 'created_at', 'updated_at'],
)]
class OauthCredentialResource extends JsonResource
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
            'provider' => new SSOProviderResource($this->getAsSSOProvider()),
            'created_at' => $this->created_at->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
