<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources\Public;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Image
 */
class ImageResource extends JsonResource
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
            'sort' => $this->sort,
            'placeholder_hash' => $this->placeholder_hash,
            'url' => $this->getImageUrl(),
            'copyright' => $this->copyright,
        ];
    }
}
