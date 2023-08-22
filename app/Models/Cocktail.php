<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Carbon\Carbon;
use Kami\Cocktail\Utils;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Symfony\Component\Uid\Ulid;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Kami\Cocktail\Models\Collection as CocktailCollection;

class Cocktail extends Model implements ImageableInterface
{
    use HasFactory,
        Searchable,
        HasImages,
        HasSlug,
        HasRating,
        HasNotes,
        HasBarAwareScope;

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

    /**
     * @return BelongsTo<User, Cocktail>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Glass, Cocktail>
     */
    public function glass(): BelongsTo
    {
        return $this->belongsTo(Glass::class);
    }

    /**
     * @return HasMany<CocktailIngredient>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(CocktailIngredient::class)->orderBy('sort');
    }

    /**
     * @return BelongsToMany<Tag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return BelongsToMany<Utensil>
     */
    public function utensils(): BelongsToMany
    {
        return $this->belongsToMany(Utensil::class);
    }

    /**
     * @return BelongsTo<CocktailMethod, Cocktail>
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(CocktailMethod::class, 'cocktail_method_id');
    }

    /**
     * @return BelongsToMany<CocktailCollection>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(CocktailCollection::class, 'collections_cocktails');
    }

    public function delete(): ?bool
    {
        $this->deleteImages();
        $this->deleteRatings();

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

        $ingredients = $this->ingredients
            ->filter(function ($cocktailIngredient) {
                return strtolower($cocktailIngredient->units) === 'ml' || str_starts_with(strtolower($cocktailIngredient->units), 'dash');
            })->toArray();

        $ingredients = array_map(function ($item) {
            if (str_starts_with(strtolower($item['units']), 'dash')) {
                $item['amount'] = $item['amount'] * 0.02;
            } else {
                $item['amount'] = $item['amount'] / 30;
            }

            return [
                'amount' => $item['amount'],
                'units' => $item['units'],
                'strength' => $item['ingredient']['strength'] ?? 0,
            ];
        }, $ingredients);

        return Utils::calculateAbv($ingredients, $this->method->dilution_percentage);
    }

    public function getMainIngredient(): ?CocktailIngredient
    {
        return $this->ingredients->first();
    }

    public function makePublic(Carbon $dateTime): self
    {
        $publicUlid = new Ulid();

        $this->public_id = $publicUlid;
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
     * @param int $userId
     * @return Builder<Cocktail>
     */
    public function scopeUserFavorites(Builder $baseQuery, int $userId): Builder
    {
        return $baseQuery->whereIn('cocktails.id', function ($query) use ($userId) {
            $query->select('cocktail_id')
                ->from('cocktail_favorites')
                ->where('user_id', $userId);
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

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->getMainImageUrl(),
            'short_ingredients' => $this->ingredients->pluck('ingredient.name'),
            'tags' => $this->tags->pluck('name'),
            'date' => $this->updated_at->format('Y-m-d H:i:s'),
            'bar_id' => $this->bar_id,
        ];
    }

    public function toShareableArray(): array
    {
        return [
            'name' => $this->name,
            'instructions' => e($this->instructions),
            'garnish' => e($this->garnish),
            'description' => e($this->description),
            'source' => $this->source,
            'tags' => $this->tags->pluck('name')->toArray(),
            'glass' => $this->glass?->name ?? null,
            'method' => $this->method?->name ?? null,
            'images' => $this->images->map(function (Image $image) {
                return [
                    'url' => $image->getImageUrl(),
                    'copyright' => $image->copyright,
                    'sort' => $image->sort,
                ];
            })->toArray(),
            'ingredients' => $this->ingredients->map(function (CocktailIngredient $cIngredient) {
                return [
                    'sort' => $cIngredient->sort ?? 0,
                    'name' => $cIngredient->ingredient->name,
                    'amount' => $cIngredient->amount,
                    'units' => $cIngredient->units,
                    'optional' => (bool) $cIngredient->optional,
                    'category' => $cIngredient->ingredient->category->name,
                    'description' => $cIngredient->ingredient->description,
                    'strength' => $cIngredient->ingredient->strength,
                    'origin' => $cIngredient->ingredient->origin,
                    'substitutes' => $cIngredient->substitutes->pluck('ingredient.name')->toArray(),
                ];
            })->toArray(),
        ];
    }

    public function toText(): string
    {
        $ingredients = $this->ingredients->map(function (CocktailIngredient $cIngredient) {
            return trim(sprintf("- \"%s\" %s %s %s", $cIngredient->ingredient->name, $cIngredient->amount, $cIngredient->units, $cIngredient->optional ? '(optional)' : ''));
        })->join("\n");

        return sprintf("%s\n%s\n\n%s\n\n%s", $this->name, e($this->description), $ingredients, e($this->instructions));
    }

    public function getNextSlug(): ?string
    {
        return $this->distinct()->orderBy('name')->limit(1)->where('name', '>', $this->name)->first()?->slug;
    }

    public function getPrevSlug(): ?string
    {
        return $this->distinct()->orderBy('name', 'desc')->limit(1)->where('name', '<', $this->name)->first()?->slug;
    }
}
