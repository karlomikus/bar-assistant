<?php

use Illuminate\Support\Facades\Route;
use Kami\Cocktail\Http\Controllers\MetricsController;
use Kami\Cocktail\Http\Middleware\CheckMetricsAccess;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', fn () => 'This is your Bar Assistant instance. Checkout /docs to see documentation.<br>If you are trying to make a request to the API, make sure you are using the correct endpoint (e.g., /api/cocktails).<br>Also make sure you are using all the required headers: Accept, Authorization.');

Route::get('/docs', fn () => view('elements'));

if (config('bar-assistant.metrics.enabled') === true) {
    Route::get('/metrics', [MetricsController::class, 'index'])->name('metrics')->middleware(CheckMetricsAccess::class);
}
