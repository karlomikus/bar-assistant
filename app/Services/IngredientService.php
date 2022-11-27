<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Kami\Cocktail\Exceptions\ImageException;
use Kami\Cocktail\Exceptions\IngredientException;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Ingredient;
use Throwable;

class IngredientService
{
    /**
     * Create a new ingredient
     *
     * @param string $name
     * @param int $ingredientCategoryId
     * @param int $userId
     * @param float $strength
     * @param string|null $description
     * @param string|null $origin
     * @param string|null $color
     * @param int|null $parentIngredientId
     * @param array<int> $images
     * @return \Kami\Cocktail\Models\Ingredient
     */
    public function createIngredient(
        string $name,
        int $ingredientCategoryId,
        int $userId,
        float $strength = 0.0,
        ?string $description = null,
        ?string $origin = null,
        ?string $color = null,
        ?int $parentIngredientId = null,
        array $images = []
    ): Ingredient {
        try {
            $ingredient = new Ingredient();
            $ingredient->name = $name;
            $ingredient->ingredient_category_id = $ingredientCategoryId;
            $ingredient->strength = $strength;
            $ingredient->description = $description;
            $ingredient->origin = $origin;
            $ingredient->color = $color;
            $ingredient->parent_ingredient_id = $parentIngredientId;
            $ingredient->user_id = $userId;
            $ingredient->save();
        } catch (Throwable $e) {
            throw new IngredientException('Error occured while creating ingredient!', 0, $e);
        }

        if (count($images) > 0) {
            try {
                $imageModels = Image::findOrFail($images);
                $ingredient->attachImages($imageModels);
            } catch (Throwable $e) {
                throw new ImageException('Error occured while attaching images to ingredient with id "' . $ingredient->id . '"', 0, $e);
            }
        }

        // Refresh model for response
        $ingredient->refresh();
        // Upsert scout index
        $ingredient->save();

        return $ingredient;
    }

    /**
     * Update an existing ingredient
     *
     * @param int $id
     * @param string $name
     * @param int $ingredientCategoryId
     * @param int $userId
     * @param float $strength
     * @param string|null $description
     * @param string|null $origin
     * @param string|null $color
     * @param int|null $parentIngredientId
     * @param array<int> $images
     * @return \Kami\Cocktail\Models\Ingredient
     */
    public function updateIngredient(
        int $id,
        string $name,
        int $ingredientCategoryId,
        int $userId,
        float $strength = 0.0,
        ?string $description = null,
        ?string $origin = null,
        ?string $color = null,
        ?int $parentIngredientId = null,
        array $images = []
    ): Ingredient {
        try {
            $ingredient = Ingredient::findOrFail($id);
            $ingredient->name = $name;
            $ingredient->ingredient_category_id = $ingredientCategoryId;
            $ingredient->strength = $strength;
            $ingredient->description = $description;
            $ingredient->origin = $origin;
            $ingredient->color = $color;
            $ingredient->parent_ingredient_id = $parentIngredientId;
            $ingredient->user_id = $userId;
            $ingredient->save();
        } catch (Throwable $e) {
            throw new IngredientException('Error occured while updating ingredient!', 0, $e);
        }

        if (count($images) > 0) {
            $ingredient->deleteImages();
            try {
                $imageModels = Image::findOrFail($images);
                $ingredient->attachImages($imageModels);
            } catch (Throwable $e) {
                throw new ImageException('Error occured while attaching images to ingredient with id "' . $ingredient->id . '"', 0, $e);
            }
        }

        // Refresh model for response
        $ingredient->refresh();
        // Upsert scout index
        $ingredient->save();

        return $ingredient;
    }
}
