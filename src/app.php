<?php

use BarAssistant\Application\DTO\CreateIngredientRequest;
use BarAssistant\Application\IngredientService;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use BarAssistant\InMemoryIngredientRepository;

require __DIR__.'/../vendor/autoload.php';

$repo = new InMemoryIngredientRepository();
$service = new IngredientService($repo);

$fruits = $service->createIngredient(new CreateIngredientRequest(
    barId: 1,
    name: 'Fruits',
    userId: 1,
));

$lemon = $service->createIngredient(new CreateIngredientRequest(
    barId: 1,
    name: 'Lemon',
    userId: 1,
    parentIngredientId: $fruits->getId()->id,
));

$lemonJuice = $service->createIngredient(new CreateIngredientRequest(
    barId: 1,
    name: 'Lemon juice',
    userId: 1,
    complexIngredientParts: [$lemon->getId()->id],
));

dd($lemon->getMaterializedPath());
