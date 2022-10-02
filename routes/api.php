<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Kami\Cocktail\Http\Controllers\CocktailController;
use Kami\Cocktail\Http\Controllers\IngredientController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('ingredients')->group(function() {
    Route::get('/', [IngredientController::class, 'index']);
});

Route::prefix('cocktails')->group(function() {
    Route::get('/', [CocktailController::class, 'index']);
    Route::post('/', [CocktailController::class, 'store']);
});
