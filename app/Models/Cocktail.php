<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Carbon\Carbon;
use Kami\Cocktail\Utils;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Symfony\Component\Uid\Ulid;
use Kami\Cocktail\SearchActions;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cocktail extends Model implements SiteSearchable
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

    /**
     * Set average rating manually to skip unnecessary SQL queres
     * @var null|float
     */
    private ?float $averageRating = null;

    /**
     * Set user rating manually to skip unnecessary SQL queres
     * -1: (defualt) Value not set, will run SQL query
     * null: No user rating
     * 0: User rated with lowest rating
     * @var null|int
     */
    private ?int $userRating = -1;

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

    public function setAverageRating(?float $rating): self
    {
        $this->averageRating = $rating;

        return $this;
    }

    public function setUserRating(?int $rating): self
    {
        $this->userRating = $rating;

        return $this;
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

        return Utils::calculateAbv($ingredients->toArray(), $this->method->dilution_percentage);
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
            'image_hash' => $this->getMainImage()?->placeholder_hash ?? null,
            'main_image_id' => $this->getMainImage()?->id ?? null,
            'short_ingredients' => $this->ingredients->pluck('ingredient.name'),
            'user_id' => $this->user_id,
            'tags' => $this->tags->pluck('name'),
            'date' => $this->updated_at->format('Y-m-d H:i:s'),
            'glass' => $this->glass->name ?? null,
            'average_rating' => $this->getAverageRating(),
            'main_ingredient_name' => $this->getMainIngredient()?->ingredient->name ?? null,
            'calculated_abv' => $this->getABV(),
            'method' => $this->method->name ?? null,
            'has_public_link' => $this->public_id !== null,
        ];
    }
}
