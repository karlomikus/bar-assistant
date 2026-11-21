<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[\Illuminate\Database\Eloquent\Attributes\WithoutTimestamps]
class Tag extends BaseModel
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\TagFactory> */
    use HasFactory;
    use HasBarAwareScope;

    public $fillable = ['name', 'bar_id'];

    /**
     * @return BelongsToMany<Cocktail, $this>
     */
    public function cocktails(): BelongsToMany
    {
        return $this->belongsToMany(Cocktail::class);
    }

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }
}
