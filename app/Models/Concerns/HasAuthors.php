<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Concerns;

use Kami\Cocktail\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasAuthors
{
    /**
     * @return BelongsTo<User, Model>
     */
    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    /**
     * @return BelongsTo<User, Model>
     */
    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_user_id');
    }
}
