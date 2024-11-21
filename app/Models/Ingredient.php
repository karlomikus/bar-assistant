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
use Kami\Cocktail\Models\ValueObjects\UnitValueObject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\IngredientFactory> */
    use HasFactory;
    use Searchable;
    use HasImages;
    use HasSlug;
    use HasBarAwareScope;
    use HasAuthors;

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

    protected $casts = [
        'strength' => 'float',
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

    public function getExternalId(): string
    {
        return Str::slug($this->name);
    }

    /**
     * @return BelongsTo<IngredientCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(IngredientCategory::class, 'ingredient_category_id', 'id');
    }

    /**
     * @return BelongsToMany<Cocktail, $this>
     */
    public function cocktails(): BelongsToMany
    {
        return $this->belongsToMany(Cocktail::class, CocktailIngredient::class);
    }

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Ingredient, $this>
     */
    public function varieties(): HasMany
    {
        return $this->hasMany(Ingredient::class, 'parent_ingredient_id', 'id');
    }

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function parentIngredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'parent_ingredient_id', 'id');
    }

    /**
     * @return HasMany<CocktailIngredientSubstitute, $this>
     */
    public function cocktailIngredientSubstitutes(): HasMany
    {
        return $this->hasMany(CocktailIngredientSubstitute::class);
    }

    /**
     * @return HasMany<ComplexIngredient, $this>
     */
    public function ingredientParts(): HasMany
    {
        return $this->hasMany(ComplexIngredient::class, 'main_ingredient_id');
    }

    /**
     * @return HasMany<IngredientPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(IngredientPrice::class);
    }

    /**
     * @return Collection<int, Cocktail>
     */
    public function cocktailsAsSubstituteIngredient(): Collection
    {
        return $this->cocktailIngredientSubstitutes->pluck('cocktailIngredient.cocktail');
    }

    public function userHasInShelf(User $user): bool
    {
        $items = $user->getShelfIngredients($this->bar_id);

        return $items->contains('ingredient_id', $this->id);
    }

    public function barHasInShelf(): bool
    {
        return $this->bar->shelfIngredients->contains('ingredient_id', $this->id);
    }

    public function userHasInShoppingList(User $user): bool
    {
        $items = $user->getShoppingListIngredients($this->bar_id);

        return $items->contains('ingredient_id', $this->id);
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

    /**
     * Return all ingredinets that use this ingredient as a substitute
     *
     * @return Collection<int, Ingredient>
     */
    public function getIngredientsUsedAsSubstituteFor(): Collection
    {
        return $this->cocktailIngredientSubstitutes->pluck('cocktailIngredient.ingredient')->unique('id')->sortBy('name');
    }

    /**
     * Return all ingredients that can be substituted with this ingredient
     *
     * @return Collection<int, $this>
     */
    public function getCanBeSubstitutedWithIngredients(): Collection
    {
        return Ingredient::query()
            ->select('ingredients.*')
            ->distinct()
            ->from('cocktail_ingredient_substitutes')
            ->join('cocktail_ingredients', 'cocktail_ingredients.id', 'cocktail_ingredient_substitutes.cocktail_ingredient_id')
            ->join('ingredients', 'ingredients.id', 'cocktail_ingredient_substitutes.ingredient_id')
            ->where('cocktail_ingredients.ingredient_id', $this->id)
            ->get();
    }

    /**
     * @return Collection<int, IngredientPrice>
     */
    public function getPricesWithConvertedUnits(?string $toUnits): Collection
    {
        if ($toUnits === null) {
            return $this->prices;
        }

        $newPrices = $this->prices->map(function (IngredientPrice $ip) use ($toUnits) {
            $convertedAmount = $ip->getAmount()->convertTo(new UnitValueObject($toUnits));
            $ip->amount = $convertedAmount->amountMin;
            $ip->units = $convertedAmount->units->value;

            return $ip;
        });

        return $newPrices;
    }

    public function delete(): ?bool
    {
        $this->deleteImages();

        return parent::delete();
    }

    /**
     * @return array<string, mixed>
     */
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
}
