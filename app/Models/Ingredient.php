<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Kami\Cocktail\Models\Concerns\HasImages;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Kami\Cocktail\Models\Concerns\IsExternalized;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kami\Cocktail\Exceptions\IngredientMoveException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kami\Cocktail\Models\ValueObjects\UnitValueObject;
use Kami\Cocktail\Models\ValueObjects\MaterializedPath;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model implements UploadableInterface, IsExternalized
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

    /**
     * @return array{strength: 'float'}
     */
    protected function casts(): array
    {
        return [
            'strength' => 'float',
        ];
    }

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
     * @return BelongsTo<Calculator, $this>
     */
    public function calculator(): BelongsTo
    {
        return $this->belongsTo(Calculator::class);
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
        $this->varieties->each(function (Ingredient $child) {
            $child->appendAsChildOf(null);
        });

        $this->deleteImages();

        return parent::delete();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, Cocktail> $models
     * @return \Illuminate\Database\Eloquent\Collection<int, Cocktail>
     */
    public function makeSearchableUsing(Collection $models): Collection
    {
        return $models->load('category', 'images');
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
            'category' => $this->getMaterializedPathAsString(),
            'bar_id' => $this->bar_id,
        ];
    }

    public function getMaterializedPath(): MaterializedPath
    {
        if ($this->isRoot()) {
            return MaterializedPath::fromString(null);
        }

        return MaterializedPath::fromString($this->materialized_path);
    }

    /**
     * @return Builder<self>
     */
    public function queryAncestors(): Builder
    {
        return $this->newQuery()->whereIn('id', $this->getMaterializedPath()->toArray());
    }

    /**
     * @return Builder<self>
     */
    public function queryDescendants(): Builder
    {
        $pathQuery = $this->getMaterializedPath()->append($this->id)->toStringPath() . '%';

        return $this->newQuery()->whereLike('materialized_path', $pathQuery);
    }

    public function isRoot(): bool
    {
        return $this->parent_ingredient_id === null;
    }

    public function appendAsChildOf(?Ingredient $parentIngredient): void
    {
        if ($this->parent_ingredient_id === null && $parentIngredient === null) {
            return;
        }

        if ($parentIngredient && $parentIngredient->isDescendantOf($this)) {
            throw new IngredientMoveException('Cannot move ingredient under its own descendant.');
        }

        if (!DB::connection()->getPdo()->inTransaction()) {
            Log::warning('Ingredient move called outside of a transaction');
        }

        $oldPath = $this->materialized_path;
        $descendants = $this->queryDescendants()->get();

        $this->withMaterializedPath($parentIngredient);
        $newPath = $this->materialized_path;

        $descendants->each(function (Ingredient $descendant) use ($oldPath, $newPath) {
            if (!blank($oldPath)) {
                $descendant->materialized_path = str_replace($oldPath, $newPath ?? '', $descendant->materialized_path);
                $descendant->save();
            }
        });

        $this->save();
    }

    public function getMaterializedPathAsString(): ?string
    {
        if ($this->materialized_path === null) {
            return null;
        }

        $pathIngredients = $this->queryAncestors()->get();

        return $pathIngredients->map(fn ($i) => $i->name)->implode(' > ');
    }

    public function isDescendantOf(Ingredient $ingredient): bool
    {
        return $this->materialized_path && str_starts_with($this->materialized_path, $ingredient->materialized_path . $ingredient->id);
    }

    public function isAncestorOf(Ingredient $ingredient): bool
    {
        return $ingredient->isDescendantOf($this);
    }

    /**
     * Sets parent ID and complete materialized path for the ingredient
     * Does not save the ingredient
     * Will throw exception if the ingredient has too many descendants
     */
    private function withMaterializedPath(?Ingredient $parentIngredient): void
    {
        if ($parentIngredient === null) {
            $this->materialized_path = null;
            $this->parent_ingredient_id = null;

            return;
        }

        $path = $parentIngredient->getMaterializedPath();
        $path = $path->append($parentIngredient->id);

        $this->parent_ingredient_id = $parentIngredient->id;
        $this->materialized_path = $path->toStringPath();
    }
}
