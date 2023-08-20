<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Kami\Cocktail\Models\Tag;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Database\DatabaseManager;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Exceptions\CocktailException;
use Kami\Cocktail\DataObjects\Cocktail\Ingredient;
use Kami\Cocktail\Models\CocktailIngredientSubstitute;
use Kami\Cocktail\DataObjects\Cocktail\Cocktail as CocktailDTO;

class CocktailService
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
            $cocktail->user_id = $cocktailDTO->userId;
            $cocktail->glass_id = $cocktailDTO->glassId;
            $cocktail->cocktail_method_id = $cocktailDTO->methodId;
            $cocktail->save();

            foreach ($cocktailDTO->ingredients as $ingredient) {
                $cIngredient = new CocktailIngredient();
                $cIngredient->ingredient_id = $ingredient->id;
                $cIngredient->amount = $ingredient->amount;
                $cIngredient->units = $ingredient->units;
                $cIngredient->optional = $ingredient->optional;
                $cIngredient->sort = $ingredient->sort;

                $cocktail->ingredients()->save($cIngredient);

                // Substitutes
                foreach ($ingredient->substitutes as $subId) {
                    $substitute = new CocktailIngredientSubstitute();
                    $substitute->ingredient_id = $subId;
                    $cIngredient->substitutes()->save($substitute);
                }
            }

            $dbTags = [];
            foreach ($cocktailDTO->tags as $tagName) {
                $tag = Tag::firstOrNew([
                    'name' => trim($tagName),
                ]);
                $tag->save();
                $dbTags[] = $tag->id;
            }

            $cocktail->tags()->attach($dbTags);
            $cocktail->utensils()->attach($cocktailDTO->utensils);
        } catch (Throwable $e) {
            $this->log->error('[COCKTAIL_SERVICE] ' . $e->getMessage());
            $this->db->rollBack();

            throw new CocktailException('Error occured while creating a cocktail!', 0, $e);
        }

        $this->db->commit();

        if (count($cocktailDTO->images) > 0) {
            try {
                $imageModels = Image::findOrFail($cocktailDTO->images);
                $cocktail->attachImages($imageModels);
            } catch (Throwable $e) {
                $this->log->error('[COCKTAIL_SERVICE] Image attach error. ' . $e->getMessage());

                throw new CocktailException('Error occured while attaching images to cocktail!', 0, $e);
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
            if ($cocktail->user_id !== 1) {
                $cocktail->user_id = $cocktailDTO->userId;
            }
            $cocktail->glass_id = $cocktailDTO->glassId;
            $cocktail->cocktail_method_id = $cocktailDTO->methodId;
            $cocktail->save();

            // TODO: Implement upsert and delete
            $cocktail->ingredients()->delete();
            foreach ($cocktailDTO->ingredients as $ingredient) {
                $cIngredient = new CocktailIngredient();
                $cIngredient->ingredient_id = $ingredient->id;
                $cIngredient->amount = $ingredient->amount;
                $cIngredient->units = $ingredient->units;
                $cIngredient->optional = $ingredient->optional;
                $cIngredient->sort = $ingredient->sort;

                $cocktail->ingredients()->save($cIngredient);

                // Substitutes
                $cIngredient->substitutes()->delete();
                foreach ($ingredient->substitutes as $subId) {
                    $substitute = new CocktailIngredientSubstitute();
                    $substitute->ingredient_id = $subId;
                    $cIngredient->substitutes()->save($substitute);
                }
            }

            $dbTags = [];
            foreach ($cocktailDTO->tags as $tagName) {
                $tag = Tag::firstOrNew([
                    'name' => trim($tagName),
                ]);
                $tag->save();
                $dbTags[] = $tag->id;
            }

            $cocktail->tags()->sync($dbTags);
            $cocktail->utensils()->sync($cocktailDTO->utensils);
        } catch (Throwable $e) {
            $this->log->error('[COCKTAIL_SERVICE] ' . $e->getMessage());
            $this->db->rollBack();

            throw new CocktailException('Error occured while updating a cocktail with id "' . $id . '"!', 0, $e);
        }

        $this->db->commit();

        if (count($cocktailDTO->images) > 0) {
            // $cocktail->deleteImages();
            try {
                $imageModels = Image::findOrFail($cocktailDTO->images);
                $cocktail->attachImages($imageModels);
            } catch (Throwable $e) {
                $this->log->error('[COCKTAIL_SERVICE] Image attach error. ' . $e->getMessage());

                throw new CocktailException('Error occured while attaching images to cocktail!', 0, $e);
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

    /**
     * Return all cocktails that user can create with
     * ingredients in his shelf
     *
     * @param array<int> $ingredientIds
     * @return \Illuminate\Support\Collection<string, mixed>
     */
    public function getCocktailsByIngredients(array $ingredientIds, ?int $limit = null): Collection
    {
        $query = $this->db->table('cocktails AS c')
            ->select('c.id')
            ->join('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'c.id')
            ->leftJoin('cocktail_ingredient_substitutes AS cis', 'cis.cocktail_ingredient_id', '=', 'ci.id')
            ->where('optional', false);

        if (config('bar-assistant.parent_ingredient_as_substitute')) {
            $query->join('ingredients AS i', function ($join) {
                $join->on('i.id', '=', 'ci.ingredient_id')->orOn('i.id', '=', 'i.parent_ingredient_id');
            })
            ->where(function ($query) use ($ingredientIds) {
                $query->whereNull('i.parent_ingredient_id')
                    ->whereIn('i.id', $ingredientIds);
            })
            ->orWhere(function ($query) use ($ingredientIds) {
                $query->whereNotNull('i.parent_ingredient_id')
                    ->where(function ($sub) use ($ingredientIds) {
                        $sub->whereIn('i.id', $ingredientIds)->orWhereIn('i.parent_ingredient_id', $ingredientIds);
                    });
            });
        } else {
            $query->join('ingredients AS i', 'i.id', '=', 'ci.ingredient_id')
            ->whereIn('i.id', $ingredientIds);
        }

        $query->orWhereIn('cis.ingredient_id', $ingredientIds)
        ->groupBy('c.id')
        ->havingRaw('COUNT(*) >= (SELECT COUNT(*) FROM cocktail_ingredients WHERE cocktail_id = c.id AND optional = false)');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->pluck('id');
    }

    /**
     * Match cocktails ingredients to users shelf ingredients
     * Does not include substitutes
     *
     * @param int $cocktailId
     * @param int $userId
     * @return array<int>
     */
    public function matchAvailableShelfIngredients(int $cocktailId, int $userId): array
    {
        return $this->db->table('ingredients AS i')
            ->select('i.id')
            ->leftJoin('user_ingredients AS ui', 'ui.ingredient_id', '=', 'i.id')
            ->where('ui.user_id', $userId)
            ->whereRaw('i.id IN (SELECT ingredient_id FROM cocktail_ingredients ci WHERE ci.cocktail_id = ?)', [$cocktailId])
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get cocktail average ratings
     *
     * @return array<int, float>
     */
    public function getCocktailAvgRatings(): array
    {
        return $this->db->table('ratings')
            ->select('rateable_id AS cocktail_id', DB::raw('AVG(rating) AS avg_rating'))
            ->where('rateable_type', Cocktail::class)
            ->groupBy('rateable_id')
            ->get()
            ->keyBy('cocktail_id')
            ->map(fn ($r) => $r->avg_rating)
            ->toArray();
    }

    /**
     * Get cocktail ids with user's rating
     *
     * @param int $userId
     * @return array
     */
    public function getCocktailUserRatings(int $userId): array
    {
        return $this->db->table('ratings')
            ->select('rateable_id AS cocktail_id', 'rating')
            ->where('rateable_type', Cocktail::class)
            ->where('user_id', $userId)
            ->groupBy('rateable_id')
            ->get()
            ->keyBy('cocktail_id')
            ->map(fn ($r) => $r->rating)
            ->toArray();
    }

    /**
     * Toggle user favorite cocktail
     *
     * @param \Kami\Cocktail\Models\User $user
     * @param int $cocktailId
     * @return bool
     */
    public function toggleFavorite(User $user, int $cocktailId): bool
    {
        $cocktail = Cocktail::find($cocktailId);

        if (!$cocktail) {
            return false;
        }

        $existing = CocktailFavorite::where('cocktail_id', $cocktailId)->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();

            return false;
        }

        $cocktailFavorite = new CocktailFavorite();
        $cocktailFavorite->cocktail_id = $cocktail->id;

        $user->favorites()->save($cocktailFavorite);

        return true;
    }

    /**
     * Get cocktail ids with number of missing user ingredients
     *
     * @param int $userId
     * @param string $direction
     * @return Collection<int, mixed>
     */
    public function getCocktailsWithMissingIngredientsCount(int $userId, string $direction = 'desc'): Collection
    {
        return $this->db->table('cocktails AS c')
            ->selectRaw('c.id, COUNT(ci.ingredient_id) - COUNT(ui.ingredient_id) AS missing_ingredients')
            ->leftJoin('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'c.id')
            ->leftJoin('user_ingredients AS ui', function ($query) use ($userId) {
                $query->on('ui.ingredient_id', '=', 'ci.ingredient_id')->where('ui.user_id', $userId);
            })
            ->groupBy('c.id')
            ->orderBy('missing_ingredients', $direction)
            ->having('missing_ingredients', '>', 0)
            ->get();
    }
}
