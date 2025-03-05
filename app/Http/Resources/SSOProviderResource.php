<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\ValueObjects\SSOProvider
 */
#[OAT\Schema(
    schema: 'SSOProvider',
    description: 'SSO Provider information',
    properties: [
        new OAT\Property(property: 'name', type: 'string', example: 'github'),
        new OAT\Property(property: 'display_name', type: 'string', example: 'GitHub'),
        new OAT\Property(property: 'enabled', description: 'Whether the provider is configured and enabled by server', type: 'boolean', example: true),
    ],
    required: ['name', 'display_name', 'enabled'],
)]
class SSOProviderResource extends JsonResource
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
            'name' => $this->provider->value,
            'display_name' => $this->provider->getPrettyName(),
            'enabled' => $this->isEnabled,
        ];
    }
}
