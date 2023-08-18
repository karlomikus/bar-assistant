<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarMembership extends Model
{
    /**
     * @return BelongsTo<Bar, BarMembership>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

    /**
     * @return BelongsTo<User, BarMembership>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
