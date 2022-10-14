<?php

use Illuminate\Support\Facades\Route;
use Kami\Cocktail\Http\Controllers\LoginController;
use Kami\Cocktail\Http\Controllers\ShelfController;
use Kami\Cocktail\Http\Controllers\CocktailController;
use Kami\Cocktail\Http\Controllers\IngredientController;
use Kami\Cocktail\Http\Controllers\UserController;

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

Route::middleware('auth:sanctum')->group(function() {

    Route::get('/user', [UserController::class, 'index']);

    Route::prefix('shelf')->group(function() {
        Route::get('/', [ShelfController::class, 'index']);
        Route::post('/{ingredientId}', [ShelfController::class, 'save']);
        Route::delete('/{ingredientId}', [ShelfController::class, 'delete']);
    });

    Route::prefix('ingredients')->group(function() {
        Route::get('/', [IngredientController::class, 'index']);
        Route::get('/categories', [IngredientController::class, 'categories']);
        Route::get('/{id}', [IngredientController::class, 'show']);
    });

    Route::prefix('cocktails')->group(function() {
        Route::get('/', [CocktailController::class, 'index'])->name('cocktails.index');
        Route::get('/user', [CocktailController::class, 'user'])->name('cocktails.user');
        Route::get('/user-favorites', [CocktailController::class, 'userFavorites'])->name('cocktails.user-favorites');
        Route::get('/{id}', [CocktailController::class, 'show'])->name('cocktails.show');
        Route::post('/{id}/favorite', [CocktailController::class, 'favorite'])->name('cocktails.favorite');
        Route::post('/', [CocktailController::class, 'store'])->name('cocktails.store');
        Route::delete('/{id}', [CocktailController::class, 'delete'])->name('cocktails.delete');
        Route::put('/{id}', [CocktailController::class, 'update'])->name('cocktails.update');
    });

    Route::prefix('images')->group(function() {
        Route::get('/{id}', [CocktailController::class, 'index'])->name('cocktails.index');
    });

});
