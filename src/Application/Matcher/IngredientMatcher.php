<?php

declare(strict_types=1);

namespace BarAssistant\Application\Matcher;

use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Application\Matcher\DTO\IngredientMatchRequest;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use BarAssistant\Domain\User\UserId;

final class IngredientMatcher
{
    /** @var array<string, Ingredient> */
    private array $matchedIngredients = [];

    public function __construct(
        private IngredientRepository $ingredientRepository,
    ) {
    }

    /**
     * Matches an ingredient by name within the bar. If no match is found, a new ingredient is created.
     *
     * @return int Ingredient ID
     */
    public function matchOrCreateByName(IngredientMatchRequest $request): int
    {
        $matchName = mb_strtolower($request->ingredientName);

        if (isset($this->matchedIngredients[$matchName])) {
            return $this->matchedIngredients[$matchName]->getId()->value;
        }

        $barIngredients = $this->ingredientRepository->findAllInBar(new BarId($request->barId));
        foreach ($barIngredients as $barIngredient) {
            $this->matchedIngredients[$barIngredient->getName()->toLowercase()] = $barIngredient;
        }

        $existingIngredient = $this->matchedIngredients[$matchName] ?? null;
        if ($existingIngredient) {
            $this->matchedIngredients[$matchName] = $existingIngredient;

            return $existingIngredient->getId()->value;
        }

        $newIngredient = Ingredient::create(
            barId: new BarId($request->barId),
            name: Name::fromString($request->ingredientName),
            authors: Authors::createdBy(new UserId($request->userId)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        return $newIngredient->getId()->value;
    }
}
