<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Cocktail\GlassId;
use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\MethodId;
use BarAssistant\Domain\Cocktail\UtensilId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\PublicStatus;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Cocktail\CocktailRepository;
use BarAssistant\Application\Cocktail\DTO\CopyCocktail;
use BarAssistant\Application\Cocktail\DTO\CocktailResult;
use BarAssistant\Application\Cocktail\DTO\CreateCocktail;
use BarAssistant\Application\Cocktail\DTO\UpdateCocktail;
use BarAssistant\Domain\Cocktail\CocktailIngredientSubstitute;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Cocktail\DTO\ForceCocktailVisibility;
use BarAssistant\Application\Cocktail\DTO\ToggleCocktailVisibility;
use BarAssistant\Domain\Cocktail\CocktailIngredient as CocktailCocktailIngredient;

final readonly class CocktailService
{
    public function __construct(private CocktailRepository $cocktailRepository)
    {
    }

    public function createCocktail(CreateCocktail $request): CocktailResult
    {
        $variantOf = null;
        if ($request->parentCocktailId) {
            $variantOf = $this->cocktailRepository->findById(new CocktailId($request->parentCocktailId));
            if ($variantOf === null) {
                throw new EntityNotFoundException('Parent cocktail not found');
            }
        }

        $cocktail = Cocktail::create(
            barId: new BarId($request->barId),
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
            glassId: $request->glassId ? new GlassId($request->glassId) : null,
            methodId: $request->methodId ? new MethodId($request->methodId) : null,
            variantOf: $variantOf?->getId(),
            author: $request->author,
        );

        foreach ($request->ingredients as $requestIngredient) {
            $substitutes = [];
            foreach ($requestIngredient->substitutes as $requestSubstitute) {
                $amountWithUnits = null;
                if ($requestSubstitute->amount !== null && $requestSubstitute->units !== null) {
                    $amountWithUnits = AmountWithUnits::from($requestSubstitute->amount, Unit::from($requestSubstitute->units), $requestSubstitute->amountMax);
                }
                $substitutes[] = CocktailIngredientSubstitute::create(
                    ingredientId: new IngredientId($requestSubstitute->ingredientId),
                    amountWithUnits: $amountWithUnits,
                );
            }

            $cocktail->addIngredient(CocktailCocktailIngredient::create(
                ingredientId: new IngredientId($requestIngredient->ingredientId),
                amountWithUnits: AmountWithUnits::from($requestIngredient->amount, Unit::from($requestIngredient->units), $requestIngredient->amountMax),
                abv: ABV::from($requestIngredient->strength),
                isOptional: $requestIngredient->isOptional,
                isSpecific: $requestIngredient->isSpecified,
                note: $requestIngredient->note,
                sortIndex: $requestIngredient->sort,
                substitutes: $substitutes,
            ));
        }

        foreach ($request->tags as $tag) {
            $cocktail->addTag($tag);
        }

        foreach ($request->images as $imageId) {
            $cocktail->addImage(new ImageId($imageId));
        }

        foreach ($request->utensils as $utensilId) {
            $cocktail->addUtensil(new UtensilId($utensilId));
        }

        $cocktail = $this->cocktailRepository->save($cocktail);

        return new CocktailResult(
            id: $cocktail->getId()->value ?? 0,
            slug: $cocktail->getSlug()?->toString() ?? '',
        );
    }

    public function updateCocktail(UpdateCocktail $request): void
    {
        $cocktail = $this->cocktailRepository->findById(new CocktailId($request->cocktailId));
        if ($cocktail === null) {
            throw new EntityNotFoundException('Cocktail not found');
        }

        $variantOf = null;
        if ($request->parentCocktailId) {
            $variantOf = $this->cocktailRepository->findById(new CocktailId($request->parentCocktailId));
            if ($variantOf === null) {
                throw new EntityNotFoundException('Parent cocktail not found');
            }
        }

        $cocktail->updateDetails(
            name: Name::fromString($request->name),
            instructions: $request->instructions,
            updatedBy: new UserId($request->userId),
            publicStatus: $cocktail->getPublicStatus(),
            glassId: $request->glassId ? new GlassId($request->glassId) : null,
            methodId: $request->methodId ? new MethodId($request->methodId) : null,
            description: $request->description,
            source: $request->source,
            garnish: $request->garnish,
            dilution: Dilution::fromFloat($request->dilution),
            variantOf: $variantOf?->getId(),
            year: $request->year,
            author: $request->author,
        );

        $cocktail->removeAllIngredients();
        foreach ($request->ingredients as $requestIngredient) {
            $substitutes = [];
            foreach ($requestIngredient->substitutes as $requestSubstitute) {
                $amountWithUnits = null;
                if ($requestSubstitute->amount !== null && $requestSubstitute->units !== null) {
                    $amountWithUnits = AmountWithUnits::from($requestSubstitute->amount, Unit::from($requestSubstitute->units), $requestSubstitute->amountMax);
                }
                $substitutes[] = CocktailIngredientSubstitute::create(
                    ingredientId: new IngredientId($requestSubstitute->ingredientId),
                    amountWithUnits: $amountWithUnits,
                );
            }

            $cocktail->addIngredient(CocktailCocktailIngredient::create(
                ingredientId: new IngredientId($requestIngredient->ingredientId),
                amountWithUnits: AmountWithUnits::from($requestIngredient->amount, Unit::from($requestIngredient->units), $requestIngredient->amountMax),
                abv: ABV::from($requestIngredient->strength),
                isOptional: $requestIngredient->isOptional,
                isSpecific: $requestIngredient->isSpecified,
                note: $requestIngredient->note,
                sortIndex: $requestIngredient->sort,
                substitutes: $substitutes,
            ));
        }

        $cocktail->clearTags();
        foreach ($request->tags as $tag) {
            $cocktail->addTag($tag);
        }

        $cocktail->setImages(array_map(static fn (int $id) => new ImageId($id), $request->images));

        $cocktail->removeAllUtensils();
        foreach ($request->utensils as $utensilId) {
            $cocktail->addUtensil(new UtensilId($utensilId));
        }

        $this->cocktailRepository->save($cocktail);
    }

    public function toggleVisibility(ToggleCocktailVisibility $request): void
    {
        $cocktail = $this->cocktailRepository->findById(new CocktailId($request->cocktailId));
        if ($cocktail === null) {
            throw new EntityNotFoundException('Cocktail not found');
        }

        if ($cocktail->isPublic() || $request->forceVisibility === ForceCocktailVisibility::Private) {
            $cocktail->makePrivate();
        } elseif ($request->forceVisibility === ForceCocktailVisibility::Public) {
            $cocktail->makePublic();
        }

        $this->cocktailRepository->save($cocktail);
    }

    public function copyCocktail(CopyCocktail $request): CocktailResult
    {
        $originalCocktail = $this->cocktailRepository->findById(new CocktailId($request->cocktailId));
        if ($originalCocktail === null) {
            throw new EntityNotFoundException('Cocktail not found');
        }

        $newCocktail = $originalCocktail->copyTo(
            barId: new BarId($request->barId),
            userId: new UserId($request->userId),
            images: array_map(static fn (int $imageId) => new ImageId($imageId), $request->images),
        );

        $newCocktail = $this->cocktailRepository->save($newCocktail);

        return new CocktailResult(
            id: $newCocktail->getId()->value ?? 0,
            slug: $newCocktail->getSlug()?->toString() ?? '',
        );
    }
}
