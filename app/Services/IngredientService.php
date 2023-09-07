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
use Kami\Cocktail\DataObjects\Ingredient\Ingredient as IngredientDTO;

class IngredientService
{
    public function __construct(
        private readonly LogManager $log,
        private readonly DatabaseManager $db,
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
            throw new IngredientException('Error occured while creating ingredient!', 0, $e);
        }

        if (count($dto->images) > 0) {
            try {
                $imageModels = Image::findOrFail($dto->images);
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

    public function updateIngredient(int $id, IngredientDTO $dto): Ingredient
    {
        if ($dto->parentIngredientId === $id) {
            throw new IngredientException('Parent ingredient is the same as the current ingredient!');
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
            throw new IngredientException('Error occured while updating ingredient!', 0, $e);
        }

        if (count($dto->images) > 0) {
            try {
                $imageModels = Image::findOrFail($dto->images);
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
        $ingredient->cocktails->each(function (Cocktail $cocktail) {
            $cocktail->abv = $cocktail->getABV();
            $cocktail->save();
        });
        $ingredient->cocktails->each(fn ($cocktail) => $cocktail->searchable());

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
