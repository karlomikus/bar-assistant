<?php
declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cocktail extends Model
{
    use HasFactory, Searchable;

    protected static function booted()
    {
        static::saved(function($cocktail) {
            app(\Laravel\Scout\EngineManager::class)->engine()->index('site_search_index')->addDocuments([
                $cocktail->toSiteSearchArray()
            ], 'key');
        });
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(CocktailIngredient::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function latestImageFilePath(): ?string
    {
        return $this->images->first()->file_path ?? null;
    }

    public function getImageUrl(): string
    {
        $filePath = $this->latestImageFilePath();
        $cocktailFilePath = 'cocktails/' . $filePath;

        if (!$filePath || !Storage::disk('app_images')->exists($cocktailFilePath)) {
            return Storage::disk('app_images')->url('cocktails/no-image.jpg');
        }

        return Storage::disk('app_images')->url($cocktailFilePath);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function toSiteSearchArray()
    {
        return [
            'key' => 'co_' . (string) $this->id,
            'id' => $this->id,
            'name' => $this->name,
            'image_url' => $this->getImageUrl(),
            'type' => 'cocktail',
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'source' => $this->source,
            'garnish' => $this->garnish,
            'image_url' => $this->getImageUrl(),
            'short_ingredients' => $this->ingredients->pluck('ingredient.name'),
            'tags' => $this->tags->pluck('name'),
            'date' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
