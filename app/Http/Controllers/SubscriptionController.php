<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Repository\CocktailRepository;
use Kami\Cocktail\Models\Collection as CocktailCollection;

class SubscriptionController extends Controller
{
    public function index(CocktailRepository $cocktailRepo, Request $request): JsonResponse
    {
        $payLink = $request->user()->newSubscription('default', $premium = 34567)
        ->returnTo(route('home'))
        ->create();
 
        return view('billing', ['payLink' => $payLink]);
    }
}
