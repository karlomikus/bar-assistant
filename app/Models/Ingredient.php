<?php
declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'strength',
        'description',
        'history',
        'origin',
        'color',
        'ingredient_category_id',
        'parent_ingredient_id',
    ];

    protected static function booted()
    {
        static::saving(function ($ing) {
            $ing->slug = Str::slug($ing->name);
        });

        static::saved(function($ing) {
            app(\Laravel\Scout\EngineManager::class)->engine()->index('site_search_index')->addDocuments([
                $ing->toSiteSearchArray()
            ], 'key');
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IngredientCategory::class, 'ingredient_category_id', 'id');
    }

    public function cocktails(): BelongsToMany
    {
        return $this->belongsToMany(Cocktail::class, CocktailIngredient::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function latestImageFilePath(): ?string
    {
        return $this->images->first()->file_path ?? null;
    }

    public function getImageUrl(): string
    {
        $filePath = $this->latestImageFilePath();
        $cocktailFilePath = 'ingredients/' . $filePath;

        if (!$filePath || !Storage::disk('app_images')->exists($cocktailFilePath)) {
            return Storage::disk('app_images')->url('ingredients/no-image.png');
        }

        return Storage::disk('app_images')->url($cocktailFilePath);
    }

    public function toSiteSearchArray()
    {
        return [
            'key' => 'in_' . (string) $this->id,
            'id' => $this->id,
            'name' => $this->name,
            'image_url' => $this->getImageUrl(),
            'type' => 'ingredient',
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image_url' => $this->getImageUrl(),
            'description' => $this->description,
            'category' => $this->category->name,
        ];
    }
}
