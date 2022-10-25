<?php

use Illuminate\Support\Facades\Route;
use Kami\Cocktail\Http\Controllers\UserController;
use Kami\Cocktail\Http\Controllers\ImageController;
use Kami\Cocktail\Http\Controllers\LoginController;
use Kami\Cocktail\Http\Controllers\ShelfController;
use Kami\Cocktail\Http\Controllers\CocktailController;
use Kami\Cocktail\Http\Controllers\HealthController;
use Kami\Cocktail\Http\Controllers\IngredientController;
use Kami\Cocktail\Http\Controllers\ShoppingListController;

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

Route::post('login', [LoginController::class, 'authenticate']);
Route::get('/version', [HealthController::class, 'version']);

Route::middleware('auth:sanctum')->group(function() {

    Route::get('/user', [UserController::class, 'index']);

    Route::prefix('shelf')->group(function() {
        Route::get('/', [ShelfController::class, 'index']);
        Route::post('/batch', [ShelfController::class, 'batch']);
        Route::post('/{ingredientId}', [ShelfController::class, 'save']);
        Route::delete('/{ingredientId}', [ShelfController::class, 'delete']);
    });

    Route::prefix('ingredients')->group(function() {
        Route::get('/', [IngredientController::class, 'index']);
        Route::post('/', [IngredientController::class, 'store']);
        Route::get('/categories', [IngredientController::class, 'categories']);
        Route::get('/{id}', [IngredientController::class, 'show']);
        Route::put('/{id}', [IngredientController::class, 'update']);
        Route::delete('/{id}', [IngredientController::class, 'delete']);
    });

    Route::prefix('cocktails')->group(function() {
        Route::get('/', [CocktailController::class, 'index'])->name('cocktails.index');
        Route::get('/random', [CocktailController::class, 'random'])->name('cocktails.random');
        Route::get('/user-shelf', [CocktailController::class, 'userShelf'])->name('cocktails.user-shelf');
        Route::get('/user-favorites', [CocktailController::class, 'userFavorites'])->name('cocktails.user-favorites');
        Route::get('/{id}', [CocktailController::class, 'show'])->name('cocktails.show');
        Route::post('/{id}/favorite', [CocktailController::class, 'favorite'])->name('cocktails.favorite');
        Route::post('/', [CocktailController::class, 'store'])->name('cocktails.store');
        Route::delete('/{id}', [CocktailController::class, 'delete'])->name('cocktails.delete');
        Route::put('/{id}', [CocktailController::class, 'update'])->name('cocktails.update');
    });

    Route::prefix('images')->group(function() {
        Route::get('/{id}', [ImageController::class, 'show']);
        Route::post('/', [ImageController::class, 'store']);
        Route::delete('/{id}', [ImageController::class, 'delete']);
    });

    Route::prefix('shopping-lists')->group(function() {
        Route::post('/batch', [ShoppingListController::class, 'batchStore']);
        Route::delete('/batch', [ShoppingListController::class, 'batchDelete']);
    });

});
