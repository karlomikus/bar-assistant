<?php

declare(strict_types=1);

namespace BarAssistant\Application;

use BarAssistant\Application\DTO\CreateIngredientRequest;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use BarAssistant\Domain\Support\Color;

final readonly class IngredientService
{
    public function __construct(private IngredientRepository $ingredientRepository)
    {
    }

    public function createIngredient(CreateIngredientRequest $ingredientRequest)
    {
        $ingredient = new Ingredient(
            barId: new BarId($ingredientRequest->barId),
            name: $ingredientRequest->name,
            description: $ingredientRequest->description,
            strength: $ingredientRequest->strength,
            origin: $ingredientRequest->origin,
            color: $ingredientRequest->color ? Color::fromHexString($ingredientRequest->color) : null,
        );

        $ingredientPartCandidates = $this->ingredientRepository->findMany($ingredientRequest->complexIngredientParts);
        foreach ($ingredientPartCandidates as $part) {
            $ingredient->addIngredientPart($part);
        }

        $ingredient = $this->ingredientRepository->save($ingredient);

        if ($ingredientRequest->parentIngredientId) {
            $parentIngredient = $this->ingredientRepository->find(new IngredientId($ingredientRequest->parentIngredientId));
            if ($parentIngredient === null) {
                throw new \InvalidArgumentException('Parent ingredient not found');
            }
            $ingredient->setAsVariantOf($parentIngredient);
        }

        return $ingredient;
    }
}
