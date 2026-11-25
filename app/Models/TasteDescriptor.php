<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'name',
    'normalized_name',
])]
class TasteDescriptor extends BaseModel
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\TasteDescriptorFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<Ingredient, $this>
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class);
    }
}
