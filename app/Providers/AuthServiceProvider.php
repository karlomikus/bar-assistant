<?php

namespace Kami\Cocktail\Providers;

use BarAssistant\Domain\Review\Review;
use Kami\Cocktail\Policies\ReviewPolicy;
use BarAssistant\Domain\Review\IngredientReview;
use Kami\Cocktail\Policies\IngredientReviewPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Review::class => ReviewPolicy::class,
        IngredientReview::class => IngredientReviewPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
