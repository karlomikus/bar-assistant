<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail;

use BarAssistant\Application\Cocktail\DTO\CreateCocktail;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\CocktailIngredient as CocktailCocktailIngredient;
use BarAssistant\Domain\Cocktail\CocktailIngredientSubstitute;
use BarAssistant\Domain\Cocktail\CocktailRepository;
use BarAssistant\Domain\Cocktail\GlassId;
use BarAssistant\Domain\Cocktail\MethodId;
use BarAssistant\Domain\Cocktail\PublicStatus;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\User\UserId;

final readonly class CocktailService
{
    public function __construct(private CocktailRepository $cocktailRepository)
    {
    }

    public function createCocktail(CreateCocktail $request): void
    {
        $variantOf = null;
        if ($request->parentCocktailId) {
            $variantOf = $this->cocktailRepository->findById(new CocktailId($request->parentCocktailId));
            if ($variantOf === null) {
                throw new EntityNotFoundException('Parent cocktail not found');
            }
        }

        $cocktail = Cocktail::create(
            barId: new BarId(142),
            name: Name::fromString($request->name),
            instructions: $request->instructions,
            authors: Authors::createdBy(new UserId($request->userId)),
            recordTimestamps: RecordTimestamps::createdNow(),
            publicStatus: PublicStatus::createPrivate(),
            description: $request->description,
            garnish: $request->garnish,
            source: $request->source,
            dilution: Dilution::fromFloat($request->dilution),
            year: $request->year,
            glassId: new GlassId($request->glassId),
            methodId: new MethodId($request->methodId),
            variantOf: $variantOf?->getId(),
        );

        foreach ($request->ingredients as $requestIngredient) {
            $substitutes = [];
            foreach ($requestIngredient->substitutes as $requestSubstitute) {
                $substitutes[] = CocktailIngredientSubstitute::create(
                    ingredientId: new IngredientId($requestSubstitute->ingredientId),
                    amountWithUnits: AmountWithUnits::from($requestSubstitute->amount, Unit::from($requestSubstitute->units), $requestSubstitute->amountMax),
                );
            }

            $cocktail->addIngredient(CocktailCocktailIngredient::create(
                ingredientId: new IngredientId($requestIngredient->ingredientId),
                amountWithUnits: AmountWithUnits::from($requestIngredient->amount, Unit::from($requestIngredient->units), $requestIngredient->amountMax),
                abv: ABV::from($requestIngredient->abv),
                isOptional: $requestIngredient->isOptional,
                isSpecific: $requestIngredient->isSpecified,
                note: $requestIngredient->note,
                substitutes: $substitutes,
            ));
        }

        foreach ($request->tags as $tag) {
            $cocktail->addTag($tag);
        }

        $this->cocktailRepository->save($cocktail);
    }
}
