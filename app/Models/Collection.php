<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<User, Collection>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Cocktail>
     */
    public function cocktails(): BelongsToMany
    {
        return $this->belongsToMany(Cocktail::class, 'collections_cocktails');
    }
}
