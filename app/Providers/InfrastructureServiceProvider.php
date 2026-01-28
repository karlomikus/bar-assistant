<?php

declare(strict_types=1);

namespace Kami\Cocktail\Providers;

use BarAssistant\Domain\Bar\BarRepository;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Domain\Image\ImageRepository;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use BarAssistant\Domain\Menu\MenuRepository;
use Illuminate\Support\ServiceProvider;
use Kami\Cocktail\Infrastructure\EloquentBarRepository;
use Kami\Cocktail\Infrastructure\EloquentImageRepository;
use Kami\Cocktail\Infrastructure\EloquentIngredientRepository;
use Kami\Cocktail\Infrastructure\EloquentMemberRepository;
use Kami\Cocktail\Infrastructure\EloquentMenuRepository;
use Kami\Cocktail\Infrastructure\EloquentPriceCategoryRepository;

class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IngredientRepository::class, EloquentIngredientRepository::class);
        $this->app->bind(PriceCategoryRepository::class, EloquentPriceCategoryRepository::class);
        $this->app->bind(BarRepository::class, EloquentBarRepository::class);
        $this->app->bind(ImageRepository::class, EloquentImageRepository::class);
        $this->app->bind(MemberRepository::class, EloquentMemberRepository::class);
        $this->app->bind(MenuRepository::class, EloquentMenuRepository::class);
    }
}
