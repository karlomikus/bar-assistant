<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Utensil extends Model
{
    use HasFactory, HasImages;

    private string $appImagesDir = 'utensils/';

    /**
     * @return HasMany<Cocktail>
     */
    public function cocktails(): HasMany
    {
        return $this->hasMany(Cocktail::class);
    }
}
