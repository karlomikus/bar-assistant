<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Models\Concerns\HasImages;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasFactory, Searchable, HasImages, HasSlug, HasBarAwareScope, HasAuthors;

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

    public function getUploadPath(): string
    {
        return 'ingredients/' . $this->bar_id . '/';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['name', 'bar_id'])
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
            'category' => $this->category?->name ?? null,
            'bar_id' => $this->bar_id,
        ];
    }

    public function share(): array
    {
        $data = [];
        $ingredientId = Str::slug($this->name);

        $data['_id'] = $ingredientId;
        if ($this->parent_ingredient_id) {
            $data['_parent_id'] = Str::slug($this->parentIngredient->name);
        }

        $data['name'] = $this->name;
        $data['description'] = $this->description;
        $data['strength'] = $this->strength;
        $data['origin'] = $this->origin;
        $data['color'] = $this->color;
        $data['category'] = $this->category?->name ?? null;

        if ($this->images->isNotEmpty()) {
            $data['images'] = $this->images->map(function (Image $image, int $key) use ($ingredientId) {
                return [
                    'sort' => $image->sort,
                    'file_name' => $ingredientId . '-' . ($key + 1) . '.' . $image->file_extension,
                    'placeholder_hash' => $image->placeholder_hash,
                    'copyright' => $image->copyright,
                ];
            })->toArray();
        }

        return $data;
    }
}
