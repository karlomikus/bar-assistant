<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Support\Str;
use Laravel\Paddle\Billable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Collection;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Kami\Cocktail\Models\Enums\BarStatusEnum;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Kami\Cocktail\Repository\CocktailRepository;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasApiTokens;
    use Notifiable;
    use Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * @return Collection<int, UserIngredient>
     */
    public function getShelfIngredients(int $barId): Collection
    {
        /** @var Collection<int, UserIngredient> */
        $emptyCollection = new Collection();

        return $this->getBarMembership($barId)?->userIngredients ?? $emptyCollection;
    }

    /**
     * @return Collection<int, UserShoppingList>
     */
    public function getShoppingListIngredients(int $barId): Collection
    {
        /** @var Collection<int, UserShoppingList> */
        $emptyCollection = new Collection();

        return $this->getBarMembership($barId)?->shoppingListIngredients ?? $emptyCollection;
    }

    /**
     * @return HasMany<BarMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(BarMembership::class);
    }

    public function joinBarAs(Bar $bar, UserRoleEnum $role = UserRoleEnum::General): BarMembership
    {
        $existingMembership = $this->getBarMembership($bar->id);
        if ($existingMembership !== null) {
            return $existingMembership;
        }

        $barMemberShip = new BarMembership();
        $barMemberShip->bar_id = $bar->id;
        $barMemberShip->user_role_id = $role->value;

        $this->memberships()->save($barMemberShip);
        $this->refresh();

        return $barMemberShip;
    }

    public function leaveBar(Bar $bar): void
    {
        $this->getBarMembership($bar->id)->delete();
    }

    /**
     * @return HasMany<Bar, $this>
     */
    public function ownedBars(): HasMany
    {
        return $this->hasMany(Bar::class, 'created_user_id');
    }

    public function getBarMembership(int $barId): ?BarMembership
    {
        $this->loadMissing('memberships');

        return $this->memberships->where('bar_id', $barId)->first();
    }

    public function hasBarMembership(int $barId): bool
    {
        return $this->getBarMembership($barId)?->id !== null;
    }

    public function isBarAdmin(int $barId): bool
    {
        return $this->hasBarRole($barId, UserRoleEnum::Admin);
    }

    public function isBarModerator(int $barId): bool
    {
        return $this->hasBarRole($barId, UserRoleEnum::Moderator);
    }

    public function isBarGeneral(int $barId): bool
    {
        return $this->hasBarRole($barId, UserRoleEnum::General);
    }

    public function isBarGuest(int $barId): bool
    {
        return $this->hasBarRole($barId, UserRoleEnum::Guest);
    }

    public function makeAnonymous(): self
    {
        $this->email = 'userdeleted' . Str::random(8);
        $this->name = 'Deleted User';
        $this->email_verified_at = null;
        $this->password = 'deleted';
        $this->remember_token = null;
        $this->created_at = now();
        $this->updated_at = now();
        $this->memberships()->delete();
        $this->deactivateBars();

        return $this;
    }

    public function hasActiveSubscription(string $subscriptionName = 'default'): bool
    {
        if (config('bar-assistant.enable_billing') === true) {
            return $this->subscribed($subscriptionName);
        }

        return true;
    }

    public function deactivateBars(): void
    {
        $this->ownedBars()->update(['status' => BarStatusEnum::Deactivated->value]);
    }

    public function activateBars(): void
    {
        $this->ownedBars()->update(['status' => BarStatusEnum::Active->value]);
    }

    /**
     * @return array<int>
     */
    public function getShelfCocktailsOnce(int $barId, bool $useParentIngredientAsSubstitute): array
    {
        return once(function () use ($barId, $useParentIngredientAsSubstitute) {
            $cocktailRepo = resolve(CocktailRepository::class);
            $userShelfIngredients = $this->getShelfIngredients($barId)->pluck('ingredient_id')->toArray();

            return $cocktailRepo->getCocktailsByIngredients(
                ingredientIds: $userShelfIngredients,
                useParentIngredientAsSubstitute: $useParentIngredientAsSubstitute
            )->values()->toArray();
        });
    }

    private function hasBarRole(int $barId, UserRoleEnum $role): bool
    {
        return $this->memberships
            ->where('bar_id', $barId)
            ->where('user_role_id', $role->value)
            ->count() > 0;
    }

    /**
     * @return HasMany<UserOAuthAccount, $this>
     */
    public function oauthAccounts(): HasMany
    {
        return $this->hasMany(UserOAuthAccount::class);
    }
}
