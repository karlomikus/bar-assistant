<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Kami\Cocktail\SearchActions;
use Spatie\Sluggable\SlugOptions;
use Kami\Cocktail\Services\Calculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cocktail extends Model implements SiteSearchable
{
    use HasFactory, Searchable, HasImages, HasSlug, HasRating;

    protected $casts = [
        'public_at' => 'datetime',
    ];

    private $appImagesDir = 'cocktails/';

    protected static function booted(): void
    {
        static::saved(function ($cocktail) {
            SearchActions::updateSearchIndex($cocktail);
        });

        static::deleted(function ($cocktail) {
            SearchActions::deleteSearchIndex($cocktail);
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function glass(): BelongsTo
    {
        return $this->belongsTo(Glass::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(CocktailIngredient::class)->orderBy('sort');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(CocktailMethod::class, 'cocktail_method_id');
    }

    public function delete(): ?bool
    {
        $this->deleteImages();
        $this->deleteRatings();

        return parent::delete();
    }

    /**
     * Calculate cocktail ABV
     * Source: Formula from https://jeffreymorgenthaler.com/
     *
     * @return null|float
     */
    public function getABV(): ?float
    {
        if ($this->cocktail_method_id === null) {
            return null;
        }

        $ingredients = $this->ingredients()
            ->select('amount', 'units', 'strength')
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->where('cocktail_ingredients.cocktail_id', $this->id)
            ->where(function ($q) {
                $q->where('cocktail_ingredients.units', 'ml')
                    ->orWhere('cocktail_ingredients.units', 'LIKE', 'dash%');
            })
            ->get()
            ->map(function ($item) {
                if (str_starts_with($item->units, 'dash')) {
                    $item->amount = $item->amount * 0.02;
                } else {
                    $item->amount = $item->amount / 30;
                }

                return $item;
            });

        return Calculator::calculateAbv($ingredients->toArray(), $this->method->dilution_percentage);
    }

    public function getMainIngredient(): ?CocktailIngredient
    {
        return $this->ingredients->first();
    }

    public function toSiteSearchArray(): array
    {
        return [
            'key' => 'co_' . (string) $this->id,
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'image_url' => $this->getMainImageUrl(),
            'type' => 'cocktail',
        ];
    }

    public function toSearchableArray(): array
    {
        // Some attributes are not searchable as per SearchActions settings
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'garnish' => $this->garnish,
            'image_url' => $this->getMainImageUrl(),
            'main_image_id' => $this->images->first()?->id ?? null,
            'short_ingredients' => $this->ingredients->pluck('ingredient.name'),
            'user_id' => $this->user_id,
            'tags' => $this->tags->pluck('name'),
            'date' => $this->updated_at->format('Y-m-d H:i:s'),
            'glass' => $this->glass->name ?? null,
            'average_rating' => $this->getAverageRating(),
            'main_ingredient_name' => $this->getMainIngredient()?->ingredient->name ?? null,
            'calculated_abv' => $this->getABV(),
            'method' => $this->method->name ?? null,
        ];
    }
}
