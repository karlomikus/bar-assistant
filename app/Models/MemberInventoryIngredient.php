<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberInventoryIngredient extends BaseModel
{
    public $timestamps = false;

    /**
     * @return BelongsTo<MemberInventory, $this>
     */
    public function memberInventory(): BelongsTo
    {
        return $this->belongsTo(MemberInventory::class);
    }

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
