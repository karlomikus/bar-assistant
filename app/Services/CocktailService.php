<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Kami\Cocktail\Models\Tag;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\DatabaseManager;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Models\CocktailIngredientSubstitute;
use Kami\Cocktail\DTO\Cocktail\Cocktail as CocktailDTO;
use Kami\Cocktail\Exceptions\ImagesNotAttachedException;

final class CocktailService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
    ) {
    }

    public function createCocktail(CocktailDTO $cocktailDTO): Cocktail
    {
        $this->db->beginTransaction();

        try {
            $cocktail = new Cocktail();
            $cocktail->name = $cocktailDTO->name;
            $cocktail->instructions = $cocktailDTO->instructions;
            $cocktail->description = $cocktailDTO->description;
            $cocktail->garnish = $cocktailDTO->garnish;
            $cocktail->source = $cocktailDTO->source;
            $cocktail->created_user_id = $cocktailDTO->userId;
            $cocktail->glass_id = $cocktailDTO->glassId;
            $cocktail->cocktail_method_id = $cocktailDTO->methodId;
            $cocktail->bar_id = $cocktailDTO->barId;
            $cocktail->save();

            foreach ($cocktailDTO->ingredients as $ingredient) {
                $cIngredient = new CocktailIngredient();
                $cIngredient->ingredient_id = $ingredient->id;
                $cIngredient->amount = $ingredient->amount;
                $cIngredient->units = $ingredient->units;
                $cIngredient->optional = $ingredient->optional;
                $cIngredient->sort = $ingredient->sort;
                $cIngredient->amount_max = $ingredient->amountMax;
                $cIngredient->note = $ingredient->note;

                $cocktail->ingredients()->save($cIngredient);

                // Substitutes
                foreach ($ingredient->substitutes as $substituteDto) {
                    $substitute = new CocktailIngredientSubstitute();
                    $substitute->ingredient_id = $substituteDto->ingredientId;
                    $substitute->amount = $substituteDto->amount;
                    $substitute->amount_max = $substituteDto->amountMax;
                    $substitute->units = $substituteDto->units;
                    $cIngredient->substitutes()->save($substitute);
                }
            }

            $dbTags = [];
            foreach (array_filter($cocktailDTO->tags) as $tagName) {
                $tag = Tag::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($tagName))])->where('bar_id', $cocktailDTO->barId)->first();
                if (!$tag) {
                    $tag = new Tag();
                    $tag->name = trim($tagName);
                    $tag->bar_id = $cocktailDTO->barId;
                    $tag->save();
                }
                $dbTags[] = $tag->id;
            }

            $cocktail->tags()->attach(array_unique($dbTags));
            $cocktail->utensils()->attach($cocktailDTO->utensils);
        } catch (Throwable $e) {
            $this->log->error('[COCKTAIL_SERVICE] ' . $e->getMessage());
            $this->db->rollBack();

            throw $e;
        }

        $this->db->commit();

        if (count($cocktailDTO->images) > 0) {
            try {
                $imageModels = Image::findOrFail($cocktailDTO->images);
                $cocktail->attachImages($imageModels);
            } catch (Throwable $e) {
                throw new ImagesNotAttachedException();
            }
        }

        // Refresh model for response
        $cocktail->refresh();
        // Calculate ABV after adding ingredients
        $cocktail->abv = $cocktail->getABV();
        // Upsert scout index
        $cocktail->save();

        return $cocktail;
    }


    public function updateCocktail(int $id, CocktailDTO $cocktailDTO): Cocktail
    {
        $this->db->beginTransaction();

        try {
            $cocktail = Cocktail::findOrFail($id);
            $cocktail->name = $cocktailDTO->name;
            $cocktail->instructions = $cocktailDTO->instructions;
            $cocktail->description = $cocktailDTO->description;
            $cocktail->garnish = $cocktailDTO->garnish;
            $cocktail->source = $cocktailDTO->source;
            $cocktail->updated_user_id = $cocktailDTO->userId;
            $cocktail->glass_id = $cocktailDTO->glassId;
            $cocktail->cocktail_method_id = $cocktailDTO->methodId;
            $cocktail->updated_at = now();
            $cocktail->save();

            Model::unguard();
            $currentIngredients = [];
            foreach ($cocktailDTO->ingredients as $ingredient) {
                $currentIngredients[] = $ingredient->id;
                $cIngredient = $cocktail->ingredients()->updateOrCreate([
                    'ingredient_id' => $ingredient->id
                ], [
                    'amount' => $ingredient->amount,
                    'units' => $ingredient->units,
                    'optional' => $ingredient->optional,
                    'sort' => $ingredient->sort,
                    'amount_max' => $ingredient->amountMax,
                    'note' => $ingredient->note,
                ]);

                // Substitutes
                $currentSubIngredients = [];
                foreach ($ingredient->substitutes as $substituteDto) {
                    $currentSubIngredients[] = $substituteDto->ingredientId;
                    $cIngredient->substitutes()->updateOrCreate([
                        'ingredient_id' => $substituteDto->ingredientId
                    ], [
                        'amount' => $substituteDto->amount,
                        'amount_max' => $substituteDto->amountMax,
                        'units' => $substituteDto->units,
                    ]);
                }
                $cIngredient->substitutes()->whereNotIn('ingredient_id', $currentSubIngredients)->delete();
            }
            Model::reguard();

            $cocktail->ingredients()->whereNotIn('ingredient_id', $currentIngredients)->delete();

            $dbTags = [];
            foreach (array_filter($cocktailDTO->tags) as $tagName) {
                $tag = Tag::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($tagName))])->where('bar_id', $cocktail->bar_id)->first();
                if (!$tag) {
                    $tag = new Tag();
                    $tag->name = trim($tagName);
                    $tag->bar_id = $cocktail->bar_id;
                    $tag->save();
                }
                $dbTags[] = $tag->id;
            }

            $cocktail->tags()->sync(array_unique($dbTags));
            $cocktail->utensils()->sync($cocktailDTO->utensils);
        } catch (Throwable $e) {
            $this->log->error('[COCKTAIL_SERVICE] ' . $e->getMessage());
            $this->db->rollBack();

            throw $e;
        }

        $this->db->commit();

        if (count($cocktailDTO->images) > 0) {
            try {
                $imageModels = Image::findOrFail($cocktailDTO->images);
                $cocktail->attachImages($imageModels);
            } catch (Throwable $e) {
                throw new ImagesNotAttachedException();
            }
        }

        // Refresh model for response
        $cocktail->refresh();
        // Calculate ABV after adding ingredients
        $cocktail->abv = $cocktail->getABV();
        // Upsert scout index
        $cocktail->save();

        return $cocktail;
    }

    public function toggleFavorite(User $user, int $cocktailId): ?CocktailFavorite
    {
        $cocktail = Cocktail::findOrFail($cocktailId);

        $barMembership = $user->getBarMembership($cocktail->bar_id);

        $existing = CocktailFavorite::where('cocktail_id', $cocktailId)->where('bar_membership_id', $barMembership->id)->first();
        if ($existing) {
            $existing->delete();

            return null;
        }

        $cocktailFavorite = new CocktailFavorite();
        $cocktailFavorite->cocktail_id = $cocktail->id;
        $cocktailFavorite->bar_membership_id = $barMembership->id;

        $barMembership->cocktailFavorites()->save($cocktailFavorite);

        return $cocktailFavorite;
    }
}
