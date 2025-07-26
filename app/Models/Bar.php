<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Spatie\Sluggable\HasSlug;
use Laravel\Scout\EngineManager;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Models\Concerns\HasImages;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Kami\Cocktail\Models\Enums\BarStatusEnum;
use Kami\Cocktail\Services\Image\ImageService;
use Kami\Cocktail\Services\MeilisearchService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bar extends Model implements UploadableInterface
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\BarFactory> */
    use HasFactory;
    use HasAuthors;
    use HasSlug;
    use HasImages;

    protected $casts = [
        'settings' => 'array',
        'is_public' => 'boolean',
    ];

    public function getUploadPath(): string
    {
        return 'logos/';
    }

    protected static function booted(): void
    {
        static::retrieved(function (Bar $bar) {
            if (
                $bar->search_token ||
                config('scout.driver') === null
            ) {
                return;
            }

            $meilisearch = resolve(MeilisearchService::class);
            $searchApiKey = $meilisearch->getSearchAPIKey();

            $bar->updateSearchToken($searchApiKey->getUid(), $searchApiKey->getKey());
        });
    }

    public function updateSearchToken(string $apiKeyUid, string $apiKey): void
    {
        Log::debug('Updating search token for bar ' . $this->id);

        /** @var \Meilisearch\Client */
        $meilisearch = resolve(EngineManager::class)->engine();

        $rules = (object) [
            '*' => (object) [
                'filter' => 'bar_id = ' . $this->id,
            ],
        ];

        $tenantToken = $meilisearch->generateTenantToken(
            $apiKeyUid,
            $rules,
            ['apiKey' => $apiKey]
        );

        $this->search_token = $tenantToken;
        $this->save();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['name'])
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(100)
            ->doNotGenerateSlugsOnUpdate();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bar_memberships')
            ->withPivot('user_role_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<BarMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(BarMembership::class);
    }

    /**
     * @return HasMany<Cocktail, $this>
     */
    public function cocktails(): HasMany
    {
        return $this->hasMany(Cocktail::class);
    }

    /**
     * @return HasMany<Ingredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * @return HasMany<BarIngredient, $this>
     */
    public function shelfIngredients(): HasMany
    {
        return $this->hasMany(BarIngredient::class);
    }

    /**
     * @return HasMany<Export, $this>
     */
    public function exports(): HasMany
    {
        return $this->hasMany(Export::class);
    }

    public function owner(): User
    {
        return $this->createdUser;
    }

    public function delete(): ?bool
    {
        /** @var ImageService */
        $imageService = app(ImageService::class);
        $imageService->cleanBarImages($this);

        // Delete export files
        foreach ($this->exports as $export) {
            $export->delete();
        }

        return parent::delete();
    }

    public function getStatus(): BarStatusEnum
    {
        if ($this->status === null) {
            return BarStatusEnum::Active;
        }

        return BarStatusEnum::tryFrom($this->status);
    }

    public function setStatus(BarStatusEnum $status): self
    {
        if ($status === BarStatusEnum::Active) {
            $this->status = null;
        } else {
            $this->status = $status->value;
        }

        return $this;
    }

    public function isAccessible(): bool
    {
        return $this->status !== BarStatusEnum::Deactivated->value;
    }

    public function getIngredientsDirectory(): string
    {
        return 'ingredients/' . $this->id . '/';
    }

    /**
     * @return array<int>
     */
    public function getShelfCocktailsOnce(): array
    {
        return once(function () {
            $cocktailRepo = resolve(CocktailService::class);
            $userShelfIngredients = $this->shelfIngredients->pluck('ingredient_id')->toArray();

            return $cocktailRepo->getCocktailsByIngredients(
                ingredientIds: $userShelfIngredients,
                barId: $this->id,
            )->values()->toArray();
        });
    }
}
