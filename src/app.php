<?php

declare(strict_types=1);

use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\CocktailIngredient;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Support\AmountWithUnits;

use BarAssistant\Domain\Support\Name;
use BarAssistant\Domain\Support\Unit;

require __DIR__.'/../vendor/autoload.php';

// $app = require_once __DIR__.'/../bootstrap/app.php';

// $kernel = $app->make(Kernel::class);

// $response = $kernel->handle(
//     $request = Request::capture()
// )->send();

// $kernel->terminate($request, $response);

$barId = 583;
$userId = 586;

$cocktailIngredient1 = new CocktailIngredient(
    ingredientId: new IngredientId(1),
    amountWithUnits: new AmountWithUnits(30.0, new Unit('ml')),
    abv: 40.0,
    isOptional: false,
    isSpecific: false,
);
$cocktail = new Cocktail(
    name: Name::fromString('asfas'),
    ingredients: [$cocktailIngredient1]
);

dump($cocktail->getABV());

dd('done');
