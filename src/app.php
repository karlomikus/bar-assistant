<?php

use BarAssistant\Application\DTO\CreateIngredientRequest;
use BarAssistant\Application\IngredientService;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientHierarchyManager;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use BarAssistant\EloquentIngredientRepository;
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
$hierarchyService = new IngredientHierarchyManager($repo);
$service = new IngredientService($repo, $hierarchyService);

$barId = 583;
$userId = 586;

Ingredient::where('bar_id', $barId)->delete();

$fruits = $service->createIngredient(new CreateIngredientRequest(
    barId: $barId,
    name: 'Fruits',
    description: 'All kinds of fruits',
    strength: 0.0,
    userId: $userId,
));
$lemon = $service->createIngredient(new CreateIngredientRequest(
    barId: $barId,
    name: 'Lemon',
    description: 'A fruit of lemon tree',
    strength: 0.0,
    userId: $userId,
));
$juices = $service->createIngredient(new CreateIngredientRequest(
    barId: $barId,
    name: 'Juices',
    description: null,
    strength: 0.0,
    userId: $userId,
));
$lemonJuice = $service->createIngredient(new CreateIngredientRequest(
    barId: $barId,
    name: 'Lemon juice',
    description: null,
    strength: 0.0,
    userId: $userId,
    complexIngredientParts: [$lemon->getId()->id],
));

// $hierarchyService->changeParent($lemon, $fruits);
// $hierarchyService->changeParent($lemonJuice, $juices);

$t = $repo->find(new IngredientId(115868));
dd('done');
