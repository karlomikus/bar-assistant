<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Services\ImageService;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bar extends Model
{
    use HasFactory;
    use HasAuthors;
    use HasSlug;

    protected $casts = [
        'settings' => 'array',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['name'])
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(100)
            ->doNotGenerateSlugsOnUpdate();
    }

    /**
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bar_memberships')
            ->withPivot('user_role_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<BarMembership>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(BarMembership::class);
    }

    /**
     * @return HasMany<Cocktail>
     */
    public function cocktails(): HasMany
    {
        return $this->hasMany(Cocktail::class);
    }

    /**
     * @return HasMany<Ingredient>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * @return HasMany<Export>
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
}
