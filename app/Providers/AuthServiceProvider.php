<?php

namespace Kami\Cocktail\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \Kami\Cocktail\Models\Cocktail::class => \Kami\Cocktail\Policies\CocktailPolicy::class,
        \Kami\Cocktail\Models\Ingredient::class => \Kami\Cocktail\Policies\IngredientPolicy::class,
        \Kami\Cocktail\Models\Image::class => \Kami\Cocktail\Policies\ImagePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
