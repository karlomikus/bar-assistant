<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources\Public;

use Kami\Cocktail\Models\Image;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Bar
 */
class BarResource extends JsonResource
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
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'images' => $this->images->map(function (Image $image) {
                return [
                    'sort' => $image->sort,
                    'placeholder_hash' => $image->placeholder_hash,
                    'url' => $image->getImageUrl(),
                    'copyright' => $image->copyright,
                ];
            }),
        ];
    }
}
