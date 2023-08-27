<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Collection;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Database\DatabaseManager;
use Kami\Cocktail\Exceptions\ImageException;
use Kami\Cocktail\Exceptions\IngredientException;

class IngredientService
{
    public function __construct(
        private readonly LogManager $log,
        private readonly DatabaseManager $db,
    ) {
    }

    /**
     * Create a new ingredient
     *
     * @param int $barId
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
        int $barId,
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
            $ingredient->bar_id = $barId;
            $ingredient->name = $name;
            $ingredient->ingredient_category_id = $ingredientCategoryId;
            $ingredient->strength = $strength;
            $ingredient->description = $description;
            $ingredient->origin = $origin;
            $ingredient->color = $color;
            $ingredient->parent_ingredient_id = $parentIngredientId;
            $ingredient->created_user_id = $userId;
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
        if ($parentIngredientId === $id) {
            throw new IngredientException('Parent ingredient is the same as the current ingredient!');
        }

        try {
            $ingredient = Ingredient::findOrFail($id);
            $ingredient->name = $name;
            $ingredient->ingredient_category_id = $ingredientCategoryId;
            $ingredient->strength = $strength;
            $ingredient->description = $description;
            $ingredient->origin = $origin;
            $ingredient->color = $color;
            $ingredient->parent_ingredient_id = $parentIngredientId;
            $ingredient->updated_user_id = $userId;
            $ingredient->updated_at = now();
            $ingredient->save();
        } catch (Throwable $e) {
            throw new IngredientException('Error occured while updating ingredient!', 0, $e);
        }

        if (count($images) > 0) {
            // $ingredient->deleteImages();
            try {
                $imageModels = Image::findOrFail($images);
                $ingredient->attachImages($imageModels);
            } catch (Throwable $e) {
                throw new ImageException('Error occured while attaching images to ingredient with id "' . $ingredient->id . '"', 0, $e);
            }
        }

        $this->log->info('[INGREDIENT_SERVICE] Ingredient updated with id:' . $ingredient->id);

        // Refresh model for response
        $ingredient->refresh();
        // Upsert scout index
        $ingredient->save();
        $ingredient->cocktails->each(fn ($cocktail) => $cocktail->searchable());
        $ingredient->cocktails->each(function (Cocktail $cocktail) {
            $cocktail->abv = $cocktail->getABV();
            $cocktail->save();
        });

        return $ingredient;
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getMainIngredientsInCocktails(int $barId): Collection
    {
        return $this->db->table('cocktail_ingredients')
            ->selectRaw('cocktail_ingredients.ingredient_id, COUNT(cocktail_ingredients.cocktail_id) AS cocktails')
            ->join('cocktails', 'cocktails.id', '=', 'cocktail_ingredients.cocktail_id')
            ->where('sort', 1)
            ->where('cocktails.bar_id', $barId)
            ->groupBy('cocktail_id')
            ->orderBy('cocktails.name', 'desc')
            ->get();
    }
}
