<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bar extends Model
{
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bar_memberships')
            ->as('membership')
            ->with('user_role_id')
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(BarMembership::class);
    }
}