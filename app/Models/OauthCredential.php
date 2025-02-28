<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Services\Auth\OauthProvider;
use Kami\Cocktail\Models\ValueObjects\SSOProvider;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthCredential extends Model
{
    protected $hidden = ['user_id', 'provider_id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProviderEnum(): OauthProvider
    {
        return OauthProvider::from($this->provider);
    }

    /**
     * @return array<SSOProvider>
     */
    public static function getAvailableProviders(): array
    {
        $providers = OauthProvider::cases();

        $availableProviders = [];
        foreach ($providers as $provider) {
            $availableProviders[] = new SSOProvider(
                $provider,
                $provider->getPrettyName(),
                self::isProviderConfigured($provider),
            );
        }

        return $availableProviders;
    }

    public static function isProviderConfigured(OauthProvider $provider): bool
    {
        return !blank(config("services.{$provider->value}.client_id"));
    }
}
