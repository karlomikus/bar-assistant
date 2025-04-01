<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Laravel\Sanctum\NewAccessToken
 */
#[OAT\Schema(
    schema: 'Token',
    description: 'Auth token resource',
    properties: [
        new OAT\Property(property: 'token', example: '1|dvWHLWuZbmWWFbjaUDla393Q9jK5Ou9ujWYPcvII', type: 'string', description: 'Access token'),
    ],
    required: ['token'],
)]
class TokenResource extends JsonResource
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
            'token' => $this->plainTextToken,
        ];
    }
}
