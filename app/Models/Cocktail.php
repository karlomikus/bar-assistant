<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Kami\Cocktail\SearchActions;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cocktail extends Model implements SiteSearchable
{
    use HasFactory, Searchable, HasImages, HasSlug;

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
        return $this->hasMany(CocktailIngredient::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function delete(): ?bool
    {
        $this->deleteImages();

        return parent::delete();
    }

    public function getABV(float $dilutionPercentage = 0.23): float
    {
        // https://jeffreymorgenthaler.com/
        // TODO: Update $dilutionPercentage based on method
        // Stirring: 20%
        // Shake: 25%
        // Building: 10%
        // TODO: Include dashes
        $alchoholicIngredients = $this->ingredients()
            ->select('amount', 'units', 'strength')
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->where('ingredients.strength', '>', 0)
            ->where('cocktail_ingredients.units', 'ml')
            ->get()
            ->map(function ($item) {
                $item->amount = $item->amount / 30;

                return $item;
            });

        $amountUsed = $alchoholicIngredients->sum('amount');
        $alcoholVolume = floatval($alchoholicIngredients->reduce(function ($carry, $item) {
            return (($item->amount * $item->strength) / 100) + $carry;
        }));

        $afterDilution = ($amountUsed * $dilutionPercentage) + $amountUsed;

        return round(($alcoholVolume / $afterDilution) * 100, 2);
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
            'source' => $this->source,
            'garnish' => $this->garnish,
            'image_url' => $this->getMainImageUrl(),
            'main_image_id' => $this->images->first()?->id ?? null,
            'short_ingredients' => $this->ingredients->pluck('ingredient.name'),
            'user_id' => $this->user_id,
            'tags' => $this->tags->pluck('name'),
            'date' => $this->updated_at->format('Y-m-d H:i:s'),
            'glass' => $this->glass->name ?? null
        ];
    }
}
