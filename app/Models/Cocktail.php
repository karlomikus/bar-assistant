<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Carbon\Carbon;
use Kami\Cocktail\Utils;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Symfony\Component\Uid\Ulid;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
    use HasFactory,
        Searchable,
        HasImages,
        HasSlug,
        HasRating,
        HasNotes,
        HasBarAwareScope,
        HasAuthors;

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
     * @return BelongsTo<Glass, Cocktail>
     */
    public function glass(): BelongsTo
    {
        return $this->belongsTo(Glass::class);
    }

    /**
     * @return BelongsTo<Bar, Cocktail>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
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
    public function getShortIngredients(): Collection
    {
        return $this->ingredients->pluck('ingredient.name');
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
            'bar_id' => $this->bar_id,
        ];
    }

    public function share(bool $useUrls = false, bool $inlineImages = false): array
    {
        $data = [];
        $cocktailId = Str::slug($this->name);

        $data['_id'] = $cocktailId;
        $data['name'] = $this->name;
        $data['instructions'] = $this->instructions;
        $data['description'] = $this->description ?? null;
        $data['garnish'] = $this->garnish;
        $data['source'] = $this->source;
        $data['tags'] = $this->tags->pluck('name')->toArray();
        $data['abv'] = $this->abv;
        $data['created_at'] = $this->created_at;
        $data['updated_at'] = $this->updated_at;

        if ($this->glass_id) {
            $data['glass'] = $this->glass->name;
        }

        if ($this->cocktail_method_id) {
            $data['method'] = $this->method->name;
        }

        $data['ingredients'] = $this->ingredients->map(function (CocktailIngredient $cIngredient) {
            $ingredient = [];
            $ingredient['_id'] = Str::slug($cIngredient->ingredient->name);
            $ingredient['sort'] = $cIngredient->sort ?? 0;
            $ingredient['name'] = $cIngredient->ingredient->name;
            $ingredient['amount'] = $cIngredient->amount;
            if ($cIngredient->amount_max) {
                $ingredient['amount_max'] = $cIngredient->amount_max;
            }
            $ingredient['units'] = $cIngredient->units;
            if ($cIngredient->note) {
                $ingredient['note'] = $cIngredient->note;
            }
            if ((bool) $cIngredient->optional === true) {
                $ingredient['optional'] = (bool) $cIngredient->optional;
            }

            if ($cIngredient->substitutes->isNotEmpty()) {
                $ingredient['substitutes'] = $cIngredient->substitutes->map(function (CocktailIngredientSubstitute $substitute) {
                    return [
                        '_id' => Str::slug($substitute->ingredient->name),
                        'name' => $substitute->ingredient->name,
                        'amount' => $substitute->amount,
                        'amount_max' => $substitute->amount_max,
                        'units' => $substitute->units,
                    ];
                })->toArray();
            }

            return $ingredient;
        })->toArray();

        if ($this->utensils->isNotEmpty()) {
            $data['utensils'] = $this->utensils->pluck('name')->toArray();
        }

        $data['images'] = $this->images->map(function (Image $image, int $key) use ($cocktailId, $useUrls) {
            $img = [
                'sort' => $image->sort,
                'placeholder_hash' => $image->placeholder_hash,
                'copyright' => $image->copyright,
            ];

            // @deprecated move everything to url
            if ($useUrls) {
                $img['url'] = $image->getImageUrl();
            } else {
                $img['file_name'] = $cocktailId . '-' . ($key + 1) . '.' . $image->file_extension;
            }

            return $img;
        })->toArray();

        return $data;
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
        return $this->distinct()->where('bar_id', $this->bar_id)->orderBy('name')->limit(1)->where('name', '>', $this->name)->first()?->slug;
    }

    public function getPrevSlug(): ?string
    {
        return $this->distinct()->where('bar_id', $this->bar_id)->orderBy('name', 'desc')->limit(1)->where('name', '<', $this->name)->first()?->slug;
    }
}
