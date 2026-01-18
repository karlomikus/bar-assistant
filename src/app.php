<?php

declare(strict_types=1);

use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\CocktailIngredient;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Common\AmountWithUnits;

use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;

require __DIR__.'/../vendor/autoload.php';

// $app = require_once __DIR__.'/../bootstrap/app.php';

// $kernel = $app->make(Kernel::class);

// $response = $kernel->handle(
//     $request = Request::capture()
// )->send();

// $kernel->terminate($request, $response);

$barId = 583;
$userId = 586;

$cocktailIngredient1 = CocktailIngredient::createRequired(
    ingredientId: new IngredientId(1),
    amountWithUnits: new AmountWithUnits(30.0, Unit::from('ml')),
    abv: ABV::from(40.0),
);
$cocktail = Cocktail::create(
    name: Name::fromString('asfas'),
    instructions: '',
    ingredients: [$cocktailIngredient1],
);

dump($cocktail->getABV());

dd('done');
