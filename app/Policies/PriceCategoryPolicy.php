<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\PriceCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class PriceCategoryPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        $hasReachedMax = true;
        if (!$user->hasActiveSubscription()) {
            $hasReachedMax = DB::table('price_categories')->where('bar_id', bar()->id)->count() < 1;
        }

        return $hasReachedMax && ($user->isBarAdmin(bar()->id) || $user->isBarModerator(bar()->id));
    }

    public function show(User $user, PriceCategory $model): bool
    {
        return $user->hasBarMembership($model->bar_id);
    }

    public function edit(User $user, PriceCategory $model): bool
    {
        return $user->isBarAdmin($model->bar_id)
            || $user->isBarModerator($model->bar_id);
    }

    public function delete(User $user, PriceCategory $model): bool
    {
        return $user->isBarAdmin($model->bar_id)
            || $user->isBarModerator($model->bar_id);
    }
}
