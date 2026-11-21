<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[\Illuminate\Database\Eloquent\Attributes\WithoutTimestamps]
class MemberInventory extends BaseModel
{
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
