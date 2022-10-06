<?php
declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Scout\Searchable;

class Cocktail extends Model
{
    use HasFactory, Searchable;

    public function ingredients(): HasMany
    {
        return $this->hasMany(CocktailIngredient::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function latestImageFilePath()
    {
        return $this->images->first()->file_path;
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'source' => $this->source,
            'garnish' => $this->garnish,
            'tags' => $this->tags->pluck('name'),
        ];
    }
}
