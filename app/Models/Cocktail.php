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

class Cocktail extends Model
{
    use HasFactory,
        Searchable,
        HasImages,
        HasSlug,
        HasRating,
        HasNotes;

    protected $casts = [
        'public_at' => 'datetime',
    ];

    private string $appImagesDir = 'cocktails/';

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
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
     * @return BelongsTo<CocktailMethod, Cocktail>
     */
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
     *
     * @return null|float
     */
    public function getABV(): ?float
    {
        if ($this->cocktail_method_id === null) {
            return null;
        }

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
            'garnish' => $this->garnish,
            'image_url' => $this->getMainImageUrl(),
            'image_hash' => $this->getMainImage()?->placeholder_hash ?? null,
            'main_image_id' => $this->getMainImage()?->id ?? null,
            'short_ingredients' => $this->ingredients->pluck('ingredient.name'),
            'user_id' => $this->user_id,
            'tags' => $this->tags->pluck('name'),
            'date' => $this->updated_at->format('Y-m-d H:i:s'),
            'glass' => $this->glass->name ?? null,
            'average_rating' => (int) round($this->ratings()->avg('rating') ?? 0),
            'main_ingredient_name' => $this->getMainIngredient()?->ingredient->name ?? null,
            'calculated_abv' => $this->getABV(),
            'method' => $this->method->name ?? null,
            'has_public_link' => $this->public_id !== null,
        ];
    }
}
