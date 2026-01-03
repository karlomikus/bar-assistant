<?php

use BarAssistant\Application\Ingredient\DTO\CreateIngredient;
use BarAssistant\Application\Ingredient\DTO\CreateIngredientPrice;
use BarAssistant\Application\Ingredient\DTO\UpdateIngredient;
use BarAssistant\Application\Ingredient\IngredientService;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;
use Kami\Cocktail\Infrastructure\EloquentIngredientRepository;
use Kami\Cocktail\Infrastructure\EloquentPriceCategoryRepository;
use Kami\Cocktail\Models\Ingredient;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);

$ingredientsRepo = new EloquentIngredientRepository();
$priceRepo = new EloquentPriceCategoryRepository();
$service = new IngredientService($ingredientsRepo, $priceRepo);

$barId = 583;
$userId = 586;

Ingredient::where('bar_id', $barId)->delete();

$spirits = $service->createIngredient(new CreateIngredient(
    barId: $barId,
    name: 'Spirits',
    description: 'All kinds of spirits',
    strength: 0.0,
    userId: $userId,
    parentIngredientId: null,
));
$whiskey = $service->createIngredient(new CreateIngredient(
    barId: $barId,
    name: 'Whiskey',
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $spirits->id,
));
$scotch = $service->createIngredient(new CreateIngredient(
    barId: $barId,
    name: 'Scotch',
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $whiskey->id,
));
$islay = $service->createIngredient(new CreateIngredient(
    barId: $barId,
    name: 'Islay Scotch',
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $scotch->id,
));
$gin = $service->createIngredient(new CreateIngredient(
    barId: $barId,
    name: 'Gin',
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $spirits->id,
));
$london = $service->createIngredient(new CreateIngredient(
    barId: $barId,
    name: 'London dry gin',
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $gin->id,
));
$speyside = $service->createIngredient(new CreateIngredient(
    barId: $barId,
    name: 'Speyside Scotch',
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $scotch->id,
));

$test = $service->updateIngredient(new UpdateIngredient(
    ingredientId: $whiskey->id,
    name: $whiskey->name,
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: null,
));

dump($speyside);

dd('done');
