<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MemberInventory extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\MemberInventoryFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return BelongsTo<BarMembership, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(BarMembership::class, 'bar_membership_id');
    }

    /**
     * @return HasMany<MemberInventoryIngredient, $this>
     */
    public function inventoryIngredients(): HasMany
    {
        return $this->hasMany(MemberInventoryIngredient::class);
    }
}
