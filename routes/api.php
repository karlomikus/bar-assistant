<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kami\Cocktail\Http\Controllers\BarController;
use Kami\Cocktail\Http\Controllers\PATController;
use Kami\Cocktail\Http\Controllers\TagController;
use Kami\Cocktail\Http\Controllers\AuthController;
use Kami\Cocktail\Http\Controllers\MenuController;
use Kami\Cocktail\Http\Controllers\NoteController;
use Kami\Cocktail\Http\Controllers\GlassController;
use Kami\Cocktail\Http\Controllers\ImageController;
use Kami\Cocktail\Http\Controllers\ShelfController;
use Kami\Cocktail\Http\Controllers\StatsController;
use Kami\Cocktail\Http\Controllers\UsersController;
use Kami\Cocktail\Http\Controllers\ExportController;
use Kami\Cocktail\Http\Controllers\ImportController;
use Kami\Cocktail\Http\Controllers\RatingController;
use Kami\Cocktail\Http\Controllers\ServerController;
use Kami\Cocktail\Http\Controllers\ExploreController;
use Kami\Cocktail\Http\Controllers\ProfileController;
use Kami\Cocktail\Http\Controllers\CocktailController;
use Kami\Cocktail\Http\Controllers\UtensilsController;
use Laravel\Paddle\Http\Controllers\WebhookController;
use Kami\Cocktail\Http\Controllers\CollectionController;
use Kami\Cocktail\Http\Controllers\IngredientController;
use Kami\Cocktail\Http\Controllers\ShoppingListController;
use Kami\Cocktail\Http\Controllers\SubscriptionController;
use Kami\Cocktail\Http\Controllers\PriceCategoryController;
use Kami\Cocktail\Http\Controllers\OAuthController;
use Kami\Cocktail\Http\Middleware\EnsureRequestHasBarQuery;
use Kami\Cocktail\Http\Controllers\CocktailMethodController;
use Kami\Cocktail\Http\Controllers\IngredientCategoryController;
use Kami\Cocktail\Http\Middleware\LocalLoginEnabled;
use Kami\Cocktail\Http\Middleware\OAuthLoginEnabled;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$apiMiddleware = ['oauth.or.sanctum'];
if (config('bar-assistant.mail_require_confirmation') === true) {
    $apiMiddleware[] = 'verified';
}

Route::prefix('auth')->group(function () {
    Route::get('config', [AuthController::class, 'authConfig']);
    Route::post('login', [AuthController::class, 'authenticate'])->name('auth.login')->middleware(LocalLoginEnabled::class);
    Route::post('register', [AuthController::class, 'register'])->middleware(LocalLoginEnabled::class);
    Route::post('forgot-password', [AuthController::class, 'passwordForgot'])->middleware(LocalLoginEnabled::class);
    Route::post('reset-password', [AuthController::class, 'passwordReset'])->middleware(LocalLoginEnabled::class);
    Route::get('verify/{id}/{hash}', [AuthController::class, 'confirmAccount'])->middleware(LocalLoginEnabled::class);
});

Route::prefix('server')->group(function () {
    Route::get('/version', [ServerController::class, 'version']);
    Route::get('/openapi', [ServerController::class, 'openApi'])->name('openapi-spec');
});

Route::prefix('images')->group(function () {
    Route::get('/{id}/thumb', [ImageController::class, 'thumb'])->name('images.thumb'); // TODO: Move this to auth middleware
});

Route::prefix('explore')->group(function () {
    Route::get('/cocktails/{ulid}', [ExploreController::class, 'cocktail']);
    Route::get('/menus/{barSlug}', [MenuController::class, 'show']);
});

Route::prefix('exports')->group(function () {
    Route::get('/{id}/download', [ExportController::class, 'download'])->name('exports.download');
});

Route::middleware($apiMiddleware)->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware(['ability:*']);
    Route::post('password-check', [AuthController::class, 'passwordCheck'])->middleware(['ability:*', LocalLoginEnabled::class]);

    Route::get('/profile', [ProfileController::class, 'show'])->middleware(['ability:*']);
    Route::post('/profile', [ProfileController::class, 'update'])->middleware(['ability:*']);

    Route::prefix('shelf')->middleware(['ability:*'])->group(function () {
        Route::post('/ingredients/batch-store', [ShelfController::class, 'batchStore'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/ingredients/batch-delete', [ShelfController::class, 'batchDelete'])->middleware(EnsureRequestHasBarQuery::class);
    });

    Route::prefix('ingredients')->group(function () {
        Route::get('/', [IngredientController::class, 'index'])->middleware([EnsureRequestHasBarQuery::class, 'ability:ingredients.read']);
        Route::post('/', [IngredientController::class, 'store'])->middleware([EnsureRequestHasBarQuery::class, 'ability:ingredients.write']);
        Route::get('/{id}', [IngredientController::class, 'show'])->name('ingredients.show')->middleware(['ability:ingredients.read']);
        Route::put('/{id}', [IngredientController::class, 'update'])->middleware(['ability:ingredients.write']);
        Route::delete('/{id}', [IngredientController::class, 'delete'])->middleware(['ability:ingredients.write']);
        Route::get('/{id}/extra', [IngredientController::class, 'extra'])->middleware(['ability:ingredients.read']);
        Route::get('/{id}/cocktails', [IngredientController::class, 'cocktails'])->middleware(['ability:ingredients.read']);
        Route::get('/{id}/substitutes', [IngredientController::class, 'substitutes'])->middleware(['ability:ingredients.read']);
    });

    Route::prefix('ingredient-categories')->middleware(['ability:*'])->group(function () {
        Route::get('/', [IngredientCategoryController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/', [IngredientCategoryController::class, 'store'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}', [IngredientCategoryController::class, 'show'])->name('ingredient-categories.show');
        Route::put('/{id}', [IngredientCategoryController::class, 'update']);
        Route::delete('/{id}', [IngredientCategoryController::class, 'delete']);
    });

    Route::prefix('cocktails')->group(function () {
        Route::get('/', [CocktailController::class, 'index'])->name('cocktails.index')->middleware([EnsureRequestHasBarQuery::class, 'ability:cocktails.read']);
        Route::get('/{id}', [CocktailController::class, 'show'])->name('cocktails.show')->middleware(['ability:cocktails.read']);
        Route::get('/{id}/share', [CocktailController::class, 'share'])->name('cocktails.share')->middleware(['ability:cocktails.read']);
        Route::post('/{id}/toggle-favorite', [CocktailController::class, 'toggleFavorite'])->name('cocktails.favorite');
        Route::post('/', [CocktailController::class, 'store'])->name('cocktails.store')->middleware([EnsureRequestHasBarQuery::class, 'ability:cocktails.write']);
        Route::delete('/{id}', [CocktailController::class, 'delete'])->name('cocktails.delete')->middleware(['ability:cocktails.write']);
        Route::put('/{id}', [CocktailController::class, 'update'])->name('cocktails.update')->middleware(['ability:cocktails.write']);
        Route::post('/{id}/public-link', [CocktailController::class, 'makePublic'])->name('cocktails.make-public')->middleware(['ability:cocktails.write']);
        Route::delete('/{id}/public-link', [CocktailController::class, 'makePrivate'])->name('cocktails.make-private')->middleware(['ability:cocktails.write']);
        Route::get('/{id}/similar', [CocktailController::class, 'similar'])->name('cocktails.similar')->middleware(['ability:cocktails.read']);
        Route::post('/{id}/copy', [CocktailController::class, 'copy'])->middleware([EnsureRequestHasBarQuery::class, 'ability:cocktails.write']);
        Route::get('/{id}/prices', [CocktailController::class, 'prices'])->middleware(['ability:cocktails.read']);

        Route::prefix('/{id}/ratings')->middleware(['ability:cocktails.write'])->group(function () {
            Route::post('/', [RatingController::class, 'rateCocktail'])->name('ratings.rate-cocktail');
            Route::delete('/', [RatingController::class, 'deleteCocktailRating'])->name('ratings.unrate-cocktail');
        });
    });

    Route::prefix('images')->middleware(['ability:*'])->group(function () {
        Route::get('/', [ImageController::class, 'index']);
        Route::get('/{id}', [ImageController::class, 'show']);
        Route::post('/', [ImageController::class, 'store']);
        Route::post('/{id}', [ImageController::class, 'update']);
        Route::delete('/{id}', [ImageController::class, 'delete']);
    });

    Route::prefix('glasses')->middleware(['ability:*'])->group(function () {
        Route::get('/', [GlassController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/', [GlassController::class, 'store'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}', [GlassController::class, 'show'])->name('glasses.show');
        Route::put('/{id}', [GlassController::class, 'update']);
        Route::delete('/{id}', [GlassController::class, 'delete']);
    });

    Route::prefix('utensils')->middleware(['ability:*'])->group(function () {
        Route::get('/', [UtensilsController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/', [UtensilsController::class, 'store'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}', [UtensilsController::class, 'show'])->name('utensils.show');
        Route::put('/{id}', [UtensilsController::class, 'update']);
        Route::delete('/{id}', [UtensilsController::class, 'delete']);
    });

    Route::prefix('tags')->middleware(['ability:*'])->group(function () {
        Route::get('/', [TagController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/', [TagController::class, 'store'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}', [TagController::class, 'show'])->name('tags.show');
        Route::put('/{id}', [TagController::class, 'update']);
        Route::delete('/{id}', [TagController::class, 'delete']);
    });

    Route::prefix('users')->middleware(['ability:*'])->group(function () {
        Route::get('/', [UsersController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/', [UsersController::class, 'store'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}', [UsersController::class, 'show'])->middleware(EnsureRequestHasBarQuery::class)->name('users.show');
        Route::put('/{id}', [UsersController::class, 'update'])->middleware(EnsureRequestHasBarQuery::class);
        Route::delete('/{id}', [UsersController::class, 'delete']);

        Route::get('/{id}/ingredients', [ShelfController::class, 'ingredients'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}/cocktails', [ShelfController::class, 'cocktails'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}/cocktails/favorites', [ShelfController::class, 'favorites'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/{id}/ingredients/batch-store', [ShelfController::class, 'batchStore'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/{id}/ingredients/batch-delete', [ShelfController::class, 'batchDelete'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}/ingredients/recommend', [ShelfController::class, 'recommend'])->middleware([EnsureRequestHasBarQuery::class, 'ability:ingredients.read']);

        Route::prefix('{id}/shopping-list')->middleware(['ability:*'])->group(function () {
            Route::get('/', [ShoppingListController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
            Route::get('/share', [ShoppingListController::class, 'share'])->middleware(EnsureRequestHasBarQuery::class);
            Route::post('/batch-store', [ShoppingListController::class, 'batchStore'])->middleware(EnsureRequestHasBarQuery::class);
            Route::post('/batch-delete', [ShoppingListController::class, 'batchDelete'])->middleware(EnsureRequestHasBarQuery::class);
        });
    });

    Route::prefix('cocktail-methods')->middleware(['ability:*'])->group(function () {
        Route::get('/', [CocktailMethodController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/', [CocktailMethodController::class, 'store'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}', [CocktailMethodController::class, 'show'])->name('cocktail-methods.show');
        Route::put('/{id}', [CocktailMethodController::class, 'update']);
        Route::delete('/{id}', [CocktailMethodController::class, 'delete']);
    });

    Route::prefix('notes')->middleware(['ability:*'])->group(function () {
        Route::get('/', [NoteController::class, 'index']);
        Route::post('/', [NoteController::class, 'store']);
        Route::get('/{id}', [NoteController::class, 'show'])->name('notes.show');
        Route::delete('/{id}', [NoteController::class, 'delete']);
    });

    Route::prefix('collections')->middleware(['ability:*'])->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/', [CollectionController::class, 'store'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}', [CollectionController::class, 'show'])->name('collection.show');
        Route::put('/{id}', [CollectionController::class, 'update']);
        Route::delete('/{id}', [CollectionController::class, 'delete']);
        Route::put('/{id}/cocktails', [CollectionController::class, 'cocktails']);
    });

    Route::prefix('import')->middleware(['throttle:importing', 'ability:*'])->group(function () {
        Route::post('/scrape', [ImportController::class, 'scrape'])->middleware(EnsureRequestHasBarQuery::class)->name('import.scrape');
        Route::post('/cocktail', [ImportController::class, 'cocktail'])->middleware(EnsureRequestHasBarQuery::class)->name('import.cocktail');
        Route::post('/file', [ImportController::class, 'file'])->middleware(EnsureRequestHasBarQuery::class)->name('import.file');
        Route::post('/ingredients', [ImportController::class, 'ingredients'])->middleware(EnsureRequestHasBarQuery::class)->name('import.ingredients');
    });

    Route::prefix('bars')->middleware(['ability:*'])->group(function () {
        Route::get('/', [BarController::class, 'index']);
        Route::post('/', [BarController::class, 'store']);
        Route::post('/join', [BarController::class, 'join']);
        Route::get('/{id}', [BarController::class, 'show'])->name('bars.show');
        Route::put('/{id}', [BarController::class, 'update']);
        Route::delete('/{id}', [BarController::class, 'delete']);
        Route::get('/{id}/memberships', [BarController::class, 'memberships']);
        Route::delete('/{id}/memberships', [BarController::class, 'leave']);
        Route::delete('/{id}/memberships/{userId}', [BarController::class, 'removeMembership']);
        Route::post('/{id}/status', [BarController::class, 'toggleBarStatus']);
        Route::post('/{id}/transfer', [BarController::class, 'transfer']);
        Route::get('/{id}/collections', [CollectionController::class, 'shared']);
        Route::get('/{id}/stats', [StatsController::class, 'index']);
        Route::get('/{id}/ingredients', [ShelfController::class, 'barIngredients']);
        Route::get('/{id}/ingredients/recommend', [ShelfController::class, 'recommendBarIngredients']);
        Route::post('/{id}/ingredients/batch-store', [ShelfController::class, 'batchStoreBarIngredients']);
        Route::post('/{id}/ingredients/batch-delete', [ShelfController::class, 'batchDeleteBarIngredients']);
        Route::get('/{id}/cocktails', [ShelfController::class, 'barCocktails']);
    });

    Route::prefix('billing')->middleware(['ability:*'])->group(function () {
        Route::get('/subscription', [SubscriptionController::class, 'subscription']);
        Route::post('/subscription', [SubscriptionController::class, 'updateSubscription']);
    });

    Route::prefix('menu')->middleware(['ability:*'])->group(function () {
        Route::get('/', [MenuController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/', [MenuController::class, 'update'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/export', [MenuController::class, 'export'])->middleware(EnsureRequestHasBarQuery::class);
    });

    Route::prefix('tokens')->middleware(['ability:*'])->group(function () {
        Route::get('/', [PATController::class, 'index']);
        Route::post('/', [PATController::class, 'store']);
        Route::delete('/{id}', [PATController::class, 'delete']);
    });

    Route::prefix('exports')->middleware(['ability:*'])->group(function () {
        Route::get('/', [ExportController::class, 'index']);
        Route::post('/', [ExportController::class, 'store'])->middleware(['throttle:exports']);
        Route::delete('/{id}', [ExportController::class, 'delete']);
        Route::post('/{id}/download', [ExportController::class, 'generateDownloadLink']);
    });

    Route::prefix('price-categories')->middleware(['ability:*'])->group(function () {
        Route::get('/', [PriceCategoryController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/', [PriceCategoryController::class, 'store'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}', [PriceCategoryController::class, 'show'])->name('price-categories.show');
        Route::put('/{id}', [PriceCategoryController::class, 'update']);
        Route::delete('/{id}', [PriceCategoryController::class, 'delete']);
    });

    Route::prefix('oauth')->group(function () {
        Route::post('/login', [OAuthController::class, 'login'])->middleware(OAuthLoginEnabled::class);
    });

    Route::prefix('oauth')->middleware(['ability:*'])->group(function () {
        Route::get('/accounts', [OAuthController::class, 'accounts']);
        Route::delete('/accounts/{id}', [OAuthController::class, 'unlinkAccount']);
    });
});

Route::post('/billing/webhook', WebhookController::class);

Route::fallback(function () {
    return response()->json([
        'type' => 'api_error',
        'message' => 'Endpoint not found.'
    ], 404);
});
