<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Kami\Cocktail\Models\Tag;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Collection;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\DatabaseManager;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Models\CocktailIngredientSubstitute;
use Kami\Cocktail\Exceptions\ImagesNotAttachedException;
use Kami\Cocktail\OpenAPI\Schemas\CocktailRequest as CocktailDTO;

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
            $cocktail->parent_cocktail_id = $cocktailDTO->parentCocktailId;
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
                $cIngredient->is_specified = $ingredient->isSpecified;

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
                throw new ImagesNotAttachedException('Unable to attach images to cocktail');
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

            if ($cocktailDTO->parentCocktailId !== $cocktail->id) {
                $cocktail->parent_cocktail_id = $cocktailDTO->parentCocktailId;
            } else {
                $this->log->warning('[COCKTAIL_SERVICE] Attempted to set parent cocktail to itself', [
                    'cocktail_id' => $cocktail->id,
                    'parent_cocktail_id' => $cocktailDTO->parentCocktailId,
                ]);
            }

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
                    'is_specified' => $ingredient->isSpecified,
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

    /**
     * Return all cocktails that user can create with
     * ingredients in his shelf
     *
     * @param array<int> $ingredientIds
     * @return \Illuminate\Support\Collection<string, mixed>
     */
    public function getCocktailsByIngredients(array $ingredientIds, int $barId, ?int $limit = null, bool $matchComplexIngredients = true): Collection
    {
        if (count($ingredientIds) === 0) {
            return collect();
        }

        // Resolve complex ingredients
        // Basically, goes through all ingredients to match ($ingredientIds) and check if they can create complex ingredients
        // If they can, that ingredient is added to the list of ingredients to match
        if ($matchComplexIngredients) {
            $additionalIngredients = $this->db->table('complex_ingredients AS ci')
                ->distinct()
                ->select('ci.main_ingredient_id')
                ->join('ingredients AS i_main', 'ci.main_ingredient_id', '=', 'i_main.id')
                ->whereIn('ci.id', function ($query) use ($ingredientIds) {
                    $query->select('ci_inner.id')
                        ->from('complex_ingredients AS ci_inner')
                        ->whereNotExists(function ($query) use ($ingredientIds) {
                            $query->select('i_ingredient.id')
                                ->from('complex_ingredients AS ci_sub')
                                ->join('ingredients AS i_ingredient', 'ci_sub.ingredient_id', '=', 'i_ingredient.id')
                                ->whereColumn('ci_sub.main_ingredient_id', 'ci_inner.main_ingredient_id')
                                ->whereNotIn('i_ingredient.id', $ingredientIds);
                        });
                })
                ->pluck('main_ingredient_id')
                ->toArray();

            $ingredientIds = array_merge($ingredientIds, $additionalIngredients);
            $ingredientIds = array_unique($ingredientIds);
        }

        // This query should handle the following cases:
        // Correctly count one match when either the main ingredient OR any of its substitutes match
        // If an ingredient can be matched either directly or through a substitute, it should only count once
        // Match any of descendant ingredients as possible substitute
        $query = $this->db->table('cocktails')
            ->select('cocktails.id')
            ->selectRaw(
                'COUNT(DISTINCT CASE
                    WHEN ingredients.id IN (' . str_repeat('?,', count($ingredientIds) - 1) . '?) THEN ingredients.id
                    WHEN cocktail_ingredient_substitutes.ingredient_id IN (' . str_repeat('?,', count($ingredientIds) - 1) . '?) THEN ingredients.id
                    WHEN cocktail_ingredients.is_specified IS FALSE AND EXISTS (
                        SELECT
                            1
                        FROM
                            ingredients
                        WHERE
                            (ingredients.parent_ingredient_id = cocktail_ingredients.ingredient_id OR materialized_path LIKE cocktail_ingredients.ingredient_id || \'/%\')
                            AND id IN (' . str_repeat('?,', count($ingredientIds) - 1) . '?)
                    ) THEN ingredients.id
                    ELSE NULL
                END) as matching_ingredients',
                [...$ingredientIds, ...$ingredientIds, ...$ingredientIds]
            )
            ->join('cocktail_ingredients', 'cocktails.id', '=', 'cocktail_ingredients.cocktail_id')
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->leftJoin('cocktail_ingredient_substitutes', 'cocktail_ingredient_substitutes.cocktail_ingredient_id', '=', 'cocktail_ingredients.id')
            ->where('cocktail_ingredients.optional', false)
            ->where('cocktails.bar_id', $barId) // This uses index on table to skip SCAN on whole cocktails table
            ->groupBy('cocktails.id')
            ->havingRaw('matching_ingredients >= (
                SELECT COUNT(*)
                FROM cocktail_ingredients ci2
                WHERE ci2.cocktail_id = cocktails.id
                AND ci2.optional = false
            )');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->pluck('id');
    }

    /**
     * Get similar cocktails, prefers cocktails with same base ingredient
     *
     * @return Collection<int, Cocktail>
     */
    public function getSimilarCocktails(Cocktail $cocktailReference, int $limitTotal = 5): Collection
    {
        $ingredients = $cocktailReference->ingredients->filter(fn ($ci) => $ci->optional === false)->pluck('ingredient_id');

        $relatedCocktails = collect();
        while ($ingredients->count() > 0) {
            $ingredients->pop();
            $possibleRelatedCocktails = $this->db->table('cocktails')
                ->select('cocktails.id')
                ->where('cocktails.id', '<>', $cocktailReference->id)
                ->where('bar_id', $cocktailReference->bar_id)
                ->whereIn('cocktails.id', function ($query) use ($ingredients) {
                    $query->select('ci.cocktail_id')
                        ->from('cocktail_ingredients AS ci')
                        ->whereIn('ci.ingredient_id', $ingredients)
                        ->where('optional', false)
                        ->groupBy('ci.cocktail_id')
                        ->havingRaw('COUNT(DISTINCT ci.ingredient_id) = ?', [$ingredients->count()]);
                })
                ->get();

            $relatedCocktails = $relatedCocktails->merge($possibleRelatedCocktails)->unique('id');
            if ($relatedCocktails->count() > $limitTotal) {
                $relatedCocktails = $relatedCocktails->take($limitTotal);
                break;
            }
        }

        return $relatedCocktails->pluck('id');
    }

    /**
     * @return Collection<array-key, mixed>
     */
    public function getTopRatedCocktails(int $barId, int $limit = 10): Collection
    {
        return $this->db->table('ratings')
            ->select('rateable_id AS id', 'cocktails.name as name', 'cocktails.slug as slug', $this->db->raw('AVG(rating) AS avg_rating'), $this->db->raw('COUNT(*) AS votes'))
            ->join('cocktails', 'cocktails.id', '=', 'ratings.rateable_id')
            ->where('rateable_type', Cocktail::class)
            ->where('cocktails.bar_id', $barId)
            ->groupBy('rateable_id')
            ->orderBy('avg_rating', 'desc')
            ->orderBy('votes', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<array-key, mixed>
     */
    public function getMemberFavoriteCocktailTags(int $barMembershipId, int $limit = 15): Collection
    {
        return $this->db->table('tags')
            ->selectRaw('tags.id, tags.name, COUNT(cocktail_favorites.cocktail_id) AS cocktails_count')
            ->join('cocktail_tag', 'cocktail_tag.tag_id', '=', 'tags.id')
            ->join('cocktail_favorites', 'cocktail_favorites.cocktail_id', '=', 'cocktail_tag.cocktail_id')
            ->where('cocktail_favorites.bar_membership_id', $barMembershipId)
            ->groupBy('tags.id')
            ->orderBy('cocktails_count', 'DESC')
            ->limit($limit)
            ->get();
    }
}
