<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Exceptions\IngredientParentException;
use Kami\Cocktail\Exceptions\ImagesNotAttachedException;
use Kami\Cocktail\DTO\Ingredient\Ingredient as IngredientDTO;

final class IngredientService
{
    public function __construct(
        private readonly LogManager $log,
    ) {
    }

    public function createIngredient(IngredientDTO $dto): Ingredient
    {
        try {
            $ingredient = new Ingredient();
            $ingredient->bar_id = $dto->barId;
            $ingredient->name = $dto->name;
            $ingredient->ingredient_category_id = $dto->ingredientCategoryId;
            $ingredient->strength = $dto->strength;
            $ingredient->description = $dto->description;
            $ingredient->origin = $dto->origin;
            $ingredient->color = $dto->color;
            $ingredient->parent_ingredient_id = $dto->parentIngredientId;
            $ingredient->created_user_id = $dto->userId;
            $ingredient->save();
        } catch (Throwable $e) {
            $this->log->error('[INGREDIENT_SERVICE] ' . $e->getMessage());

            throw $e;
        }

        if (count($dto->images) > 0) {
            try {
                $imageModels = Image::findOrFail($dto->images);
                $ingredient->attachImages($imageModels);
            } catch (Throwable $e) {
                throw new ImagesNotAttachedException();
            }
        }

        // Refresh model for response
        $ingredient->refresh();
        // Upsert scout index
        $ingredient->save();

        return $ingredient;
    }

    public function updateIngredient(int $id, IngredientDTO $dto): Ingredient
    {
        if ($dto->parentIngredientId === $id) {
            throw new IngredientParentException('Parent ingredient is the same as the current ingredient!');
        }

        try {
            $ingredient = Ingredient::findOrFail($id);
            $ingredient->name = $dto->name;
            $ingredient->ingredient_category_id = $dto->ingredientCategoryId;
            $ingredient->strength = $dto->strength;
            $ingredient->description = $dto->description;
            $ingredient->origin = $dto->origin;
            $ingredient->color = $dto->color;
            $ingredient->parent_ingredient_id = $dto->parentIngredientId;
            $ingredient->updated_user_id = $dto->userId;
            $ingredient->updated_at = now();
            $ingredient->save();
        } catch (Throwable $e) {
            $this->log->error('[INGREDIENT_SERVICE] ' . $e->getMessage());

            throw $e;
        }

        if (count($dto->images) > 0) {
            try {
                $imageModels = Image::findOrFail($dto->images);
                $ingredient->attachImages($imageModels);
            } catch (Throwable $e) {
                throw new ImagesNotAttachedException();
            }
        }

        $this->log->info('[INGREDIENT_SERVICE] Ingredient updated with id:' . $ingredient->id);

        // Refresh model for response
        $ingredient->refresh();
        // Upsert scout index
        $ingredient->save();
        $ingredient->cocktails->each(function (Cocktail $cocktail) {
            $cocktail->abv = $cocktail->getABV();
            $cocktail->save();
        });
        $ingredient->cocktails->each(fn ($cocktail) => $cocktail->searchable());

        return $ingredient;
    }
}
