<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasFactory, Searchable, HasImages, HasSlug;

    private string $appImagesDir = 'ingredients/';

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

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * @return BelongsTo<IngredientCategory, Ingredient>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(IngredientCategory::class, 'ingredient_category_id', 'id');
    }

    /**
     * @return BelongsToMany<Cocktail>
     */
    public function cocktails(): BelongsToMany
    {
        return $this->belongsToMany(Cocktail::class, CocktailIngredient::class);
    }

    /**
     * @return BelongsTo<User, Ingredient>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Ingredient>
     */
    public function varieties(): HasMany
    {
        return $this->hasMany(Ingredient::class, 'parent_ingredient_id', 'id');
    }

    /**
     * @return BelongsTo<Ingredient, Ingredient>
     */
    public function parentIngredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'parent_ingredient_id', 'id');
    }

    /**
     * @return HasMany<CocktailIngredientSubstitute>
     */
    public function cocktailIngredientSubstitutes(): HasMany
    {
        return $this->hasMany(CocktailIngredientSubstitute::class);
    }

    /**
     * @return Collection<int, Cocktail>
     */
    public function cocktailsAsSubstituteIngredient(): Collection
    {
        return $this->cocktailIngredientSubstitutes->pluck('cocktailIngredient.cocktail');
    }

    /**
     * @return Collection<int, Ingredient>
     */
    public function getAllRelatedIngredients(): Collection
    {
        // This creates "Related" group of the ingredients "on-the-fly"
        if ($this->parent_ingredient_id !== null) {
            return $this->parentIngredient
                ->varieties
                ->sortBy('name')
                ->filter(fn ($ing) => $ing->id !== $this->id)
                ->push($this->parentIngredient);
        }

        return $this->varieties;
    }

    public function delete(): ?bool
    {
        $this->deleteImages();

        return parent::delete();
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'image_url' => $this->getMainImageUrl(),
            'description' => $this->description,
            'category' => $this->category->name,
            'strength_abv' => $this->strength,
            'color' => $this->color ?? 'No color',
            'origin' => $this->origin ?? 'No origin',
        ];
    }
}
