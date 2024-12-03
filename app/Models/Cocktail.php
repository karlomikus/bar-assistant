<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Carbon\Carbon;
use Brick\Money\Money;
use Kami\Cocktail\Utils;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Kami\RecipeUtils\Converter;
use Symfony\Component\Uid\Ulid;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Models\Concerns\HasNotes;
use Kami\Cocktail\Models\Concerns\HasImages;
use Kami\Cocktail\Models\Concerns\HasRating;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Kami\Cocktail\Models\Collection as CocktailCollection;

class Cocktail extends Model implements UploadableInterface
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CocktailFactory> */
    use HasFactory;
    use Searchable;
    use HasImages;
    use HasSlug;
    use HasRating;
    use HasNotes;
    use HasBarAwareScope;
    use HasAuthors;

    protected $casts = [
        'public_at' => 'datetime',
    ];

    public function getUploadPath(): string
    {
        return 'cocktails/' . $this->bar_id . '/';
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
     * @return BelongsTo<Glass, $this>
     */
    public function glass(): BelongsTo
    {
        return $this->belongsTo(Glass::class);
    }

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

    /**
     * @return HasMany<CocktailIngredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(CocktailIngredient::class)->orderBy('sort');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return BelongsToMany<Utensil, $this>
     */
    public function utensils(): BelongsToMany
    {
        return $this->belongsToMany(Utensil::class);
    }

    /**
     * @return BelongsTo<CocktailMethod, $this>
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(CocktailMethod::class, 'cocktail_method_id');
    }

    /**
     * @return BelongsToMany<CocktailCollection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(CocktailCollection::class, 'collections_cocktails');
    }

    public function delete(): ?bool
    {
        $this->deleteImages();
        $this->deleteRatings();
        $this->deleteNotes();

        return parent::delete();
    }

    /**
     * Calculate cocktail ABV
     *
     * @return null|float
     */
    public function getABV(): ?float
    {
        if ($this->cocktail_method_id === null) {
            return null;
        }

        $this->loadMissing('ingredients.ingredient');

        $ingredientsForABV = [];
        foreach ($this->ingredients as $cocktailIngredient) {
            $unitFrom = Units::tryFrom($cocktailIngredient->units);
            if (!$unitFrom) {
                continue;
            }

            $amount = Converter::fromTo($cocktailIngredient->amount, $unitFrom, Units::Oz);

            $ingredientsForABV[] = [
                'amount' => $amount,
                'units' => $unitFrom->value,
                'strength' => $cocktailIngredient->ingredient->strength ?? 0,
            ];
        }

        return Utils::calculateAbv($ingredientsForABV, $this->method->dilution_percentage);
    }

    public function getVolume(): float
    {
        $ingredients = $this->ingredients->map(function ($i) {
            return $i->getAmount();
        })->toArray();

        return Utils::calculateVolume($ingredients);
    }

    public function getAlcoholUnits(): float
    {
        if ($this->cocktail_method_id === null) {
            return 0.0;
        }

        return (float) number_format(($this->getVolume() * $this->getABV()) / 1000, 2);
    }

    public function getCalories(): int
    {
        if ($this->getABV() === null) {
            return 0;
        }

        // It's important to note that the calorie content of mixed drinks can vary significantly
        // depending on the type and amount of mixers used. Drinks with sugary mixers or syrups
        // will generally have higher calorie counts.
        $averageAlcCalories = 7;

        return (int) floor($this->getVolume() * ($this->getABV() / 100) * $averageAlcCalories);
    }

    public function getMainIngredient(): ?CocktailIngredient
    {
        return $this->ingredients->first();
    }

    public function makePublic(Carbon $dateTime): self
    {
        $publicUlid = new Ulid();

        $this->public_id = (string) $publicUlid;
        $this->public_at = $dateTime;
        $this->public_expires_at = null;
        $this->save();

        return $this;
    }

    public function makePrivate(): self
    {
        $this->public_id = null;
        $this->public_at = null;
        $this->public_expires_at = null;
        $this->save();

        return $this;
    }

    public function addToCollection(CocktailCollection $collection): void
    {
        $collection->cocktails()->attach($this);
    }

    /**
     * Only user favorites
     *
     * @param Builder<Cocktail> $baseQuery
     * @param int $barMembershipId
     * @return Builder<Cocktail>
     */
    public function scopeUserFavorites(Builder $baseQuery, int $barMembershipId): Builder
    {
        return $baseQuery->whereIn('cocktails.id', function ($query) use ($barMembershipId) {
            $query->select('cocktail_id')
                ->from('cocktail_favorites')
                ->where('bar_membership_id', $barMembershipId);
        });
    }

    /**
     * Include ratings information
     *
     * @param Builder<Cocktail> $query
     * @param int $userId
     * @return Builder<Cocktail>
     */
    public function scopeWithRatings(Builder $query, int $userId): Builder
    {
        $this->loadMissing('ratings');

        return $query->addSelect([
            'average_rating' => Rating::selectRaw('AVG(rating)')
                ->whereColumn('rateable_id', 'cocktails.id')
                ->whereColumn('rateable_type', Cocktail::class),
            'user_rating' => Rating::select('rating')
                ->whereColumn('rateable_id', 'cocktails.id')
                ->whereColumn('rateable_type', Cocktail::class)
                ->where('user_id', $userId),
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    public function getIngredientNames(): Collection
    {
        return $this->ingredients->pluck('ingredient.name');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, Cocktail> $models
     * @return \Illuminate\Database\Eloquent\Collection<int, Cocktail>
     */
    public function makeSearchableUsing(Collection $models): Collection
    {
        return $models->load('ingredients.ingredient', 'tags', 'images');
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->getMainImageThumbUrl(),
            'short_ingredients' => $this->getIngredientNames(),
            'tags' => $this->tags->pluck('name'),
            'bar_id' => $this->bar_id,
        ];
    }

    public function getNextCocktail(): ?Cocktail
    {
        return $this->distinct()->where('bar_id', $this->bar_id)->orderBy('name')->limit(1)->where('name', '>', $this->name)->first();
    }

    public function getPrevCocktail(): ?Cocktail
    {
        return $this->distinct()->where('bar_id', $this->bar_id)->orderBy('name', 'desc')->limit(1)->where('name', '<', $this->name)->first();
    }

    public function getUserShelfMatchPercentage(User $user): float
    {
        $currentShelf = $user->getShelfIngredients($this->bar_id);
        $totalIngredients = $this->ingredients->count();
        $matchIngredients = $this->ingredients->filter(function (CocktailIngredient $ci) use ($currentShelf) {
            return $currentShelf->contains('ingredient_id', $ci->ingredient_id);
        })->count();

        return ($matchIngredients / $totalIngredients) * 100;
    }

    public function getBarShelfMatchPercentage(): float
    {
        $currentShelf = $this->bar->shelfIngredients;
        $totalIngredients = $this->ingredients->count();
        $matchIngredients = $this->ingredients->filter(function (CocktailIngredient $ci) use ($currentShelf) {
            return $currentShelf->contains('ingredient_id', $ci->ingredient_id);
        })->count();

        return ($matchIngredients / $totalIngredients) * 100;
    }

    public function inUserShelf(User $user): bool
    {
        $currentShelf = $user->getShelfIngredients($this->bar_id);
        foreach ($this->ingredients as $ci) {
            if (!$currentShelf->contains('ingredient_id', $ci->ingredient_id) && !$ci->optional) {
                return false;
            }
        }

        return true;
    }

    public function inBarShelf(): bool
    {
        $currentShelf = $this->bar->shelfIngredients;
        foreach ($this->ingredients as $ci) {
            if (!$currentShelf->contains('ingredient_id', $ci->ingredient_id) && !$ci->optional) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function asJsonLDSchema(): array
    {
        return [
            "@context" => "https://schema.org",
            "@type" => "Recipe",
            "author" => [
                '@type' => 'Organization',
                'name' => "Recipe exported from Bar Assistant"
            ],
            "name" => e($this->name),
            "datePublished" => $this->created_at->format('Y-m-d'),
            "description" => e($this->description),
            "image" => [
                "@type" => "ImageObject",
                "author" => e($this->getMainImage()?->copyright),
                "url" => $this->getMainImage()?->getImageUrl(),
            ],
            'recipeInstructions' => e($this->instructions),
            "cookingMethod" => $this->method?->name,
            "recipeYield" => "1 drink",
            "recipeCategory" => "Drink",
            "recipeCuisine" => "Cocktail",
            "keywords" => $this->tags->pluck('name')->implode(', '),
            "recipeIngredient" => $this->ingredients->map(function (CocktailIngredient $ci) {
                return $ci->amount . ' ' . $ci->units . ' ' . $ci->ingredient->name;
            }),
        ];
    }

    public function calculatePrice(PriceCategory $priceCategory): Money
    {
        $totalPrice = Money::of(0, $priceCategory->getCurrency()->value)->toRational();

        /** @var CocktailIngredient */
        foreach ($this->ingredients as $cocktailIngredient) {
            $pricePerPour = $cocktailIngredient->getConvertedPricePerUse($priceCategory);

            if ($pricePerPour === null) {
                continue;
            }

            $totalPrice = $totalPrice->plus($pricePerPour);
        }

        return $totalPrice->to(new DefaultContext(), RoundingMode::DOWN);
    }
}
