<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Exception
 */
class ErrorResource extends JsonResource
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
            'type' => 'api_error',
            'message' => $this->getMessage(),
        ];
    }
}
