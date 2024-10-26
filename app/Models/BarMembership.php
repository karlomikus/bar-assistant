<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kami\Cocktail\Models\Collection as CocktailCollection;

class BarMembership extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\BarMembershipFactory> */
    use HasFactory;

    protected $casts = [
        'is_shelf_public' => 'boolean',
        'use_parent_as_substitute' => 'boolean',
    ];

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

    /**
     * @return BelongsTo<UserRole, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'user_role_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<UserIngredient, $this>
     */
    public function userIngredients(): HasMany
    {
        return $this->hasMany(UserIngredient::class);
    }

    /**
     * @return HasMany<UserShoppingList, $this>
     */
    public function shoppingListIngredients(): HasMany
    {
        return $this->hasMany(UserShoppingList::class);
    }

    /**
     * @return HasMany<CocktailFavorite, $this>
     */
    public function cocktailFavorites(): HasMany
    {
        return $this->hasMany(CocktailFavorite::class);
    }

    /**
     * @return HasMany<CocktailCollection, $this>
     */
    public function cocktailCollections(): HasMany
    {
        return $this->hasMany(CocktailCollection::class);
    }
}
