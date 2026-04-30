<?php

declare(strict_types=1);

namespace Kami\Cocktail\Providers;

use Illuminate\Support\ServiceProvider;
use BarAssistant\Domain\Bar\BarRepository;
use BarAssistant\Domain\Menu\MenuRepository;
use BarAssistant\Domain\Note\NoteRepository;
use BarAssistant\Domain\User\UserRepository;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Domain\Image\ImageRepository;
use BarAssistant\Domain\Export\ExportRepository;
use BarAssistant\Domain\Export\FileTokenService;
use BarAssistant\Domain\Rating\RatingRepository;
use BarAssistant\Domain\Cocktail\GlassRepository;
use BarAssistant\Application\Export\ExportService;
use BarAssistant\Domain\Bar\BarInventoryRepository;
use BarAssistant\Domain\Cocktail\UtensilRepository;
use BarAssistant\Domain\Cocktail\CocktailRepository;
use BarAssistant\Domain\Bar\MemberInventoryRepository;
use Kami\Cocktail\Infrastructure\EloquentBarRepository;
use BarAssistant\Domain\Calculator\CalculatorRepository;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use Kami\Cocktail\Infrastructure\EloquentMenuRepository;
use Kami\Cocktail\Infrastructure\EloquentNoteRepository;
use Kami\Cocktail\Infrastructure\EloquentUserRepository;
use BarAssistant\Domain\Cocktail\CocktailMatchRepository;
use Kami\Cocktail\Infrastructure\EloquentGlassRepository;
use Kami\Cocktail\Infrastructure\EloquentImageRepository;
use BarAssistant\Domain\Cocktail\CocktailMethodRepository;
use Kami\Cocktail\Infrastructure\EloquentExportRepository;
use Kami\Cocktail\Infrastructure\EloquentMemberRepository;
use Kami\Cocktail\Infrastructure\EloquentRatingRepository;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use Kami\Cocktail\Infrastructure\EloquentUtensilRepository;
use Kami\Cocktail\Infrastructure\EloquentCocktailRepository;
use BarAssistant\Domain\Ingredient\IngredientMatchRepository;
use BarAssistant\Domain\Cocktail\CocktailCollectionRepository;
use Kami\Cocktail\Infrastructure\EloquentCalculatorRepository;
use Kami\Cocktail\Infrastructure\EloquentIngredientRepository;
use BarAssistant\Domain\Recommendation\RecommendationRepository;
use Kami\Cocktail\Infrastructure\EloquentBarInventoryRepository;
use Kami\Cocktail\Infrastructure\EloquentCocktailMatchRepository;
use Kami\Cocktail\Infrastructure\EloquentPriceCategoryRepository;
use Kami\Cocktail\Infrastructure\EloquentCocktailMethodRepository;
use Kami\Cocktail\Infrastructure\EloquentRecommendationRepository;
use Kami\Cocktail\Infrastructure\EloquentIngredientMatchRepository;
use Kami\Cocktail\Infrastructure\EloquentMemberInventoryRepository;
use Kami\Cocktail\Infrastructure\EloquentCocktailCollectionRepository;
use Kami\Cocktail\Infrastructure\EloquentIngredientHierarchyRepository;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyRepository;

class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IngredientRepository::class, EloquentIngredientRepository::class);
        $this->app->bind(IngredientHierarchyRepository::class, EloquentIngredientHierarchyRepository::class);
        $this->app->bind(PriceCategoryRepository::class, EloquentPriceCategoryRepository::class);
        $this->app->bind(BarInventoryRepository::class, EloquentBarInventoryRepository::class);
        $this->app->bind(BarRepository::class, EloquentBarRepository::class);
        $this->app->bind(ImageRepository::class, EloquentImageRepository::class);
        $this->app->bind(GlassRepository::class, EloquentGlassRepository::class);
        $this->app->bind(UtensilRepository::class, EloquentUtensilRepository::class);
        $this->app->bind(CocktailMethodRepository::class, EloquentCocktailMethodRepository::class);
        $this->app->bind(MemberRepository::class, EloquentMemberRepository::class);
        $this->app->bind(MemberInventoryRepository::class, EloquentMemberInventoryRepository::class);
        $this->app->bind(RatingRepository::class, EloquentRatingRepository::class);
        $this->app->bind(MenuRepository::class, EloquentMenuRepository::class);
        $this->app->bind(CocktailRepository::class, EloquentCocktailRepository::class);
        $this->app->bind(CocktailCollectionRepository::class, EloquentCocktailCollectionRepository::class);
        $this->app->bind(CocktailMatchRepository::class, EloquentCocktailMatchRepository::class);
        $this->app->bind(IngredientMatchRepository::class, EloquentIngredientMatchRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(NoteRepository::class, EloquentNoteRepository::class);
        $this->app->bind(CalculatorRepository::class, EloquentCalculatorRepository::class);
        $this->app->bind(RecommendationRepository::class, EloquentRecommendationRepository::class);
        $this->app->bind(ExportRepository::class, EloquentExportRepository::class);
        $this->app->bind(FileTokenService::class, fn () => new FileTokenService(config('app.key')));
        $this->app->bind(ExportService::class);
    }
}
