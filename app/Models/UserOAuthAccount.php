<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kami\Cocktail\Models\User;

class UserOAuthAccount extends Model
{
    protected $table = 'user_oauth_accounts';

    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\UserOAuthAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_id',
        'provider_user_id',
    ];

    protected $casts = [
        'provider_user_id' => 'string',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
