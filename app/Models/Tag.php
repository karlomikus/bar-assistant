<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory, HasBarAwareScope;

    public $timestamps = false;

    public $fillable = ['name', 'bar_id'];

    /**
     * @return BelongsToMany<Cocktail>
     */
    public function cocktails(): BelongsToMany
    {
        return $this->belongsToMany(Cocktail::class);
    }

    /**
     * @return BelongsTo<Bar, Collection>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }
}
