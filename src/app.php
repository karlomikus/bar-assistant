<?php

use BarAssistant\Application\DTO\CreateIngredientDTO;
use BarAssistant\Application\DTO\IngredientPriceRequest;
use BarAssistant\Application\DTO\UpdateIngredientDTO;
use BarAssistant\Application\IngredientService;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientHierarchyManager;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use BarAssistant\EloquentIngredientRepository;
use BarAssistant\EloquentPriceCategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;
use Kami\Cocktail\Models\Ingredient;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);

$repo = new EloquentIngredientRepository();
$priceRepo = new EloquentPriceCategoryRepository();
// $hierarchyService = new IngredientHierarchyManager($repo);
$service = new IngredientService($repo, $priceRepo);

$barId = 583;
$userId = 586;

Ingredient::where('bar_id', $barId)->delete();

$spirits = $service->createIngredient(new CreateIngredientDTO(
    barId: $barId,
    name: 'Spirits',
    description: 'All kinds of spirits',
    strength: 0.0,
    userId: $userId,
    prices: [
        new IngredientPriceRequest(
            priceCategoryId: 27,
            price: 500,
            amount: 1.0,
            units: 'kg',
            description: 'Base price for fruits',
        ),
    ],
));
$whiskey = $service->createIngredient(new CreateIngredientDTO(
    barId: $barId,
    name: 'Whiskey',
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $spirits->id,
));
$scotch = $service->createIngredient(new CreateIngredientDTO(
    barId: $barId,
    name: 'Scotch',
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $whiskey->id,
));
$islay = $service->createIngredient(new CreateIngredientDTO(
    barId: $barId,
    name: 'Islay Scotch',
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $scotch->id,
));
$gin = $service->createIngredient(new CreateIngredientDTO(
    barId: $barId,
    name: 'Gin',
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $spirits->id,
));
$london = $service->createIngredient(new CreateIngredientDTO(
    barId: $barId,
    name: 'London dry gin',
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $gin->id,
));



$test = $service->updateIngredient(new UpdateIngredientDTO(
    ingredientId: $whiskey->id,
    name: $whiskey->name,
    description: null,
    strength: 0.0,
    userId: $userId,
    parentIngredientId: $gin->id,
));
// $juices3 = $service->updateIngredient(new UpdateIngredientDTO(
//     ingredientId: $lemon->id,
//     name: $lemon->name,
//     description: null,
//     strength: 0.0,
//     userId: $userId,
//     parentIngredientId: $juices3->id,
// ));
// $lemonJuice = $service->createIngredient(new CreateIngredientDTO(
//     barId: $barId,
//     name: 'Lemon juice',
//     description: null,
//     strength: 0.0,
//     userId: $userId,
//     complexIngredientParts: [$lemon->id],
// ));
// $lemonJuice2 = $service->createIngredient(new CreateIngredientDTO(
//     barId: $barId,
//     name: 'Lemon juice 2',
//     description: null,
//     strength: 0.0,
//     userId: $userId,
// ));

// $service->updateIngredient(new UpdateIngredientDTO(
//     ingredientId: $lemon->id,
//     name: 'Lemon juice 2',
//     userId: $userId,
// ));

// $hierarchyService->changeParent($lemon, $fruits);
// $hierarchyService->changeParent($lemonJuice, $juices);
// $hierarchyService->changeParent($lemonJuice2, $lemonJuice);
// $hierarchyService->changeParent($lemonJuice2, $juices);

$t = $repo->findById(new IngredientId(115868));
dd('done');
