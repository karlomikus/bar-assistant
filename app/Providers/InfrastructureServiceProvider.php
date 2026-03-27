<?php

declare(strict_types=1);

namespace Kami\Cocktail\Providers;

use Illuminate\Support\ServiceProvider;
use BarAssistant\Domain\Bar\BarRepository;
use BarAssistant\Domain\Menu\MenuRepository;
use BarAssistant\Domain\User\UserRepository;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Domain\Image\ImageRepository;
use BarAssistant\Domain\Cocktail\GlassRepository;
use BarAssistant\Domain\Cocktail\UtensilRepository;
use BarAssistant\Domain\Cocktail\CocktailRepository;
use Kami\Cocktail\Infrastructure\EloquentBarRepository;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use Kami\Cocktail\Infrastructure\EloquentMenuRepository;
use Kami\Cocktail\Infrastructure\EloquentUserRepository;
use Kami\Cocktail\Infrastructure\EloquentGlassRepository;
use Kami\Cocktail\Infrastructure\EloquentImageRepository;
use BarAssistant\Domain\Cocktail\CocktailMethodRepository;
use Kami\Cocktail\Infrastructure\EloquentMemberRepository;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use Kami\Cocktail\Infrastructure\EloquentUtensilRepository;
use Kami\Cocktail\Infrastructure\EloquentCocktailRepository;
use BarAssistant\Domain\Cocktail\CocktailCollectionRepository;
use Kami\Cocktail\Infrastructure\EloquentIngredientRepository;
use Kami\Cocktail\Infrastructure\EloquentPriceCategoryRepository;
use Kami\Cocktail\Infrastructure\EloquentCocktailMethodRepository;
use Kami\Cocktail\Infrastructure\EloquentCocktailCollectionRepository;

class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IngredientRepository::class, EloquentIngredientRepository::class);
        $this->app->bind(PriceCategoryRepository::class, EloquentPriceCategoryRepository::class);
        $this->app->bind(BarRepository::class, EloquentBarRepository::class);
        $this->app->bind(ImageRepository::class, EloquentImageRepository::class);
        $this->app->bind(GlassRepository::class, EloquentGlassRepository::class);
        $this->app->bind(UtensilRepository::class, EloquentUtensilRepository::class);
        $this->app->bind(CocktailMethodRepository::class, EloquentCocktailMethodRepository::class);
        $this->app->bind(MemberRepository::class, EloquentMemberRepository::class);
        $this->app->bind(MenuRepository::class, EloquentMenuRepository::class);
        $this->app->bind(CocktailRepository::class, EloquentCocktailRepository::class);
        $this->app->bind(CocktailCollectionRepository::class, EloquentCocktailCollectionRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
    }
}
