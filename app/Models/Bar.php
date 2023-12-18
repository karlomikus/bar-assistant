<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Services\ImageService;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bar extends Model
{
    use HasFactory, HasAuthors;

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

    public function owner(): User
    {
        return $this->createdUser;
    }

    public function delete(): ?bool
    {
        /** @var ImageService */
        $imageService = app(ImageService::class);
        $imageService->cleanBarImages($this);

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
        return $this->status !== BarStatusEnum::Deactivated;
    }
}
