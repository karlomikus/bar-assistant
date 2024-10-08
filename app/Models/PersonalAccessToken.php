<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

/**
 * @property string $name
 * @property array<string> $abilities
 * @property string $last_used_at
 * @property string $created_at
 * @property string $expires_at
 */
class PersonalAccessToken extends SanctumToken
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\PersonalAccessTokenFactory> */
    use HasFactory;
}
