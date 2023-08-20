<?php

use Illuminate\Support\Facades\Route;
use Kami\Cocktail\Http\Controllers\BarController;
use Kami\Cocktail\Http\Controllers\TagController;
use Kami\Cocktail\Http\Controllers\AuthController;
use Kami\Cocktail\Http\Controllers\NoteController;
use Kami\Cocktail\Http\Controllers\GlassController;
use Kami\Cocktail\Http\Controllers\ImageController;
use Kami\Cocktail\Http\Controllers\ShelfController;
use Kami\Cocktail\Http\Controllers\StatsController;
use Kami\Cocktail\Http\Controllers\UsersController;
use Kami\Cocktail\Http\Controllers\ImportController;
use Kami\Cocktail\Http\Controllers\RatingController;
use Kami\Cocktail\Http\Controllers\ScrapeController;
use Kami\Cocktail\Http\Controllers\ServerController;
use Kami\Cocktail\Http\Controllers\ExploreController;
use Kami\Cocktail\Http\Controllers\ProfileController;
use Kami\Cocktail\Http\Controllers\CocktailController;
use Kami\Cocktail\Http\Controllers\UtensilsController;
use Kami\Cocktail\Http\Controllers\CollectionController;
use Kami\Cocktail\Http\Controllers\IngredientController;
use Kami\Cocktail\Http\Controllers\ShoppingListController;
use Kami\Cocktail\Http\Controllers\CocktailMethodController;
use Kami\Cocktail\Http\Controllers\IngredientCategoryController;
use Kami\Cocktail\Http\Middleware\EnsureRequestHasBarQuery;

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

$authMiddleware = 'auth:sanctum';
if (config('bar-assistant.disable_login') === true) {
    $authMiddleware = 'auth:force-login';
}

Route::get('/', [ServerController::class, 'index']);

Route::post('login', [AuthController::class, 'authenticate'])->name('auth.login');
Route::post('register', [AuthController::class, 'register']);

Route::prefix('server')->group(function() {
    Route::get('/version', [ServerController::class, 'version']);
    Route::get('/openapi', [ServerController::class, 'openApi'])->name('openapi-spec');
});

Route::prefix('images')->group(function() {
    Route::get('/{id}/thumb', [ImageController::class, 'thumb']); // TODO: Move this to auth middleware
});

Route::prefix('explore')->group(function() {
    Route::get('/cocktails/{ulid}', [ExploreController::class, 'cocktail']);
});

Route::middleware($authMiddleware)->group(function() {

    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/user', [ProfileController::class, 'show']);
    Route::post('/user', [ProfileController::class, 'update']);

    Route::prefix('shelf')->group(function() {
        Route::get('/cocktails', [ShelfController::class, 'cocktails'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/ingredients', [ShelfController::class, 'ingredients'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/ingredients/batch-store', [ShelfController::class, 'batchStore'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/ingredients/batch-delete', [ShelfController::class, 'batchDelete'])->middleware(EnsureRequestHasBarQuery::class);
    });

    Route::prefix('ingredients')->group(function() {
        Route::get('/', [IngredientController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/', [IngredientController::class, 'store'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}', [IngredientController::class, 'show'])->name('ingredients.show');
        Route::put('/{id}', [IngredientController::class, 'update']);
        Route::delete('/{id}', [IngredientController::class, 'delete']);
        Route::get('/{id}/extra', [IngredientController::class, 'extra'])->middleware(EnsureRequestHasBarQuery::class);
    });

    Route::prefix('ingredient-categories')->group(function() {
        Route::get('/', [IngredientCategoryController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/', [IngredientCategoryController::class, 'store'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/{id}', [IngredientCategoryController::class, 'show'])->name('ingredient-categories.show');
        Route::put('/{id}', [IngredientCategoryController::class, 'update']);
        Route::delete('/{id}', [IngredientCategoryController::class, 'delete']);
    });

    Route::prefix('cocktails')->group(function() {
        Route::get('/', [CocktailController::class, 'index'])->name('cocktails.index');
        Route::get('/{id}', [CocktailController::class, 'show'])->name('cocktails.show');
        Route::get('/{id}/share', [CocktailController::class, 'share'])->name('cocktails.share');
        Route::post('/{id}/toggle-favorite', [CocktailController::class, 'toggleFavorite'])->name('cocktails.favorite');
        Route::post('/', [CocktailController::class, 'store'])->name('cocktails.store');
        Route::delete('/{id}', [CocktailController::class, 'delete'])->name('cocktails.delete');
        Route::put('/{id}', [CocktailController::class, 'update'])->name('cocktails.update');
        Route::post('/{id}/public-link', [CocktailController::class, 'makePublic'])->name('cocktails.make-public');
        Route::delete('/{id}/public-link', [CocktailController::class, 'makePrivate'])->name('cocktails.make-private');
        Route::get('/{id}/similar', [CocktailController::class, 'similar'])->name('cocktails.similar');
    });

    Route::prefix('images')->group(function() {
        Route::get('/', [ImageController::class, 'index']);
        Route::get('/{id}', [ImageController::class, 'show']);
        // Route::get('/{id}/thumb', [ImageController::class, 'thumb']);
        Route::post('/', [ImageController::class, 'store']);
        Route::post('/{id}', [ImageController::class, 'update']);
        Route::delete('/{id}', [ImageController::class, 'delete']);
    });

    Route::prefix('shopping-list')->group(function() {
        Route::get('/', [ShoppingListController::class, 'index'])->middleware(EnsureRequestHasBarQuery::class);
        Route::get('/share', [ShoppingListController::class, 'share'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/batch-store', [ShoppingListController::class, 'batchStore'])->middleware(EnsureRequestHasBarQuery::class);
        Route::post('/batch-delete', [ShoppingListController::class, 'batchDelete'])->middleware(EnsureRequestHasBarQuery::class);
    });

    Route::prefix('glasses')->group(function() {
        Route::get('/find', [GlassController::class, 'find']);
        Route::get('/', [GlassController::class, 'index']);
        Route::post('/', [GlassController::class, 'store']);
        Route::get('/{id}', [GlassController::class, 'show'])->name('glasses.show');
        Route::put('/{id}', [GlassController::class, 'update']);
        Route::delete('/{id}', [GlassController::class, 'delete']);
    });

    Route::prefix('utensils')->group(function() {
        Route::get('/', [UtensilsController::class, 'index']);
        Route::post('/', [UtensilsController::class, 'store']);
        Route::get('/{id}', [UtensilsController::class, 'show'])->name('utensils.show');
        Route::put('/{id}', [UtensilsController::class, 'update']);
        Route::delete('/{id}', [UtensilsController::class, 'delete']);
    });

    Route::prefix('tags')->group(function() {
        Route::get('/', [TagController::class, 'index']);
        Route::post('/', [TagController::class, 'store']);
        Route::get('/{id}', [TagController::class, 'show'])->name('tags.show');
        Route::put('/{id}', [TagController::class, 'update']);
        Route::delete('/{id}', [TagController::class, 'delete']);
    });

    Route::prefix('ratings')->group(function() {
        Route::post('/cocktails/{id}', [RatingController::class, 'rateCocktail']);
        Route::delete('/cocktails/{id}', [RatingController::class, 'deleteCocktailRating']);
    });

    Route::prefix('users')->group(function() {
        Route::get('/', [UsersController::class, 'index']);
        Route::post('/', [UsersController::class, 'store']);
        Route::get('/{id}', [UsersController::class, 'show'])->name('users.show');
        Route::put('/{id}', [UsersController::class, 'update']);
        Route::delete('/{id}', [UsersController::class, 'delete']);
    });

    Route::prefix('stats')->group(function() {
        Route::get('/', [StatsController::class, 'index']);
    });

    Route::prefix('cocktail-methods')->group(function() {
        Route::get('/', [CocktailMethodController::class, 'index']);
        Route::post('/', [CocktailMethodController::class, 'store']);
        Route::get('/{id}', [CocktailMethodController::class, 'show'])->name('cocktail-methods.show');
        Route::put('/{id}', [CocktailMethodController::class, 'update']);
        Route::delete('/{id}', [CocktailMethodController::class, 'delete']);
    });

    Route::prefix('scrape')->group(function() {
        Route::post('/cocktail', [ScrapeController::class, 'cocktail']);
    });

    Route::prefix('notes')->group(function() {
        Route::post('/', [NoteController::class, 'store']);
        Route::get('/{id}', [NoteController::class, 'show'])->name('notes.show');
        Route::delete('/{id}', [NoteController::class, 'delete']);
    });

    Route::prefix('collections')->group(function() {
        Route::get('/', [CollectionController::class, 'index']);
        Route::post('/', [CollectionController::class, 'store']);
        Route::get('/{id}', [CollectionController::class, 'show'])->name('collection.show');
        Route::put('/{id}', [CollectionController::class, 'update']);
        Route::delete('/{id}', [CollectionController::class, 'delete']);
        Route::post('/{id}/cocktails', [CollectionController::class, 'cocktails']);
        Route::put('/{id}/cocktails/{cocktailId}', [CollectionController::class, 'cocktail']);
        Route::delete('/{id}/cocktails/{cocktailId}', [CollectionController::class, 'deleteResourceFromCollection']);
        Route::get('/{id}/share', [CollectionController::class, 'share'])->name('collection.share');
    });

    Route::prefix('import')->group(function() {
        Route::post('/cocktail', [ImportController::class, 'cocktail']);
    });

    Route::prefix('bars')->group(function() {
        Route::get('/', [BarController::class, 'index']);
        Route::post('/', [BarController::class, 'store']);
    });
});

Route::fallback(function() {
    return response()->json([
        'type' => 'api_error',
        'message' => 'Endpoint not found.'
    ], 404);
});
