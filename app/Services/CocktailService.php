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
use Illuminate\Database\DatabaseManager;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Exceptions\CocktailException;
use Kami\Cocktail\DataObjects\Cocktail\Ingredient;
use Kami\Cocktail\Models\CocktailIngredientSubstitute;

class CocktailService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
    ) {
    }

    /**
     * Create a new cocktail
     *
     * @param string $name
     * @param string $instructions
     * @param array<Ingredient> $ingredients
     * @param int $userId
     * @param string|null $description
     * @param string|null $garnish
     * @param string|null $cocktailSource
     * @param array<int> $images
     * @param array<string> $tags
     * @param int|null $glassId
     * @param int|null $cocktailMethodId
     * @return \Kami\Cocktail\Models\Cocktail
     */
    public function createCocktail(
        string $name,
        string $instructions,
        array $ingredients,
        int $userId,
        ?string $description = null,
        ?string $garnish = null,
        ?string $cocktailSource = null,
        array $images = [],
        array $tags = [],
        ?int $glassId = null,
        ?int $cocktailMethodId = null
    ): Cocktail {
        $this->db->beginTransaction();

        try {
            $cocktail = new Cocktail();
            $cocktail->name = $name;
            $cocktail->instructions = $instructions;
            $cocktail->description = $description;
            $cocktail->garnish = $garnish;
            $cocktail->source = $cocktailSource;
            $cocktail->user_id = $userId;
            $cocktail->glass_id = $glassId;
            $cocktail->cocktail_method_id = $cocktailMethodId;
            $cocktail->save();

            foreach ($ingredients as $ingredient) {
                if (!($ingredient instanceof Ingredient)) {
                    $this->log->warning('[COCKTAIL_SERVICE] Ingredient in ingredients array is of wrong type!');
                    continue;
                }

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
            foreach ($tags as $tagName) {
                $tag = Tag::firstOrNew([
                    'name' => trim($tagName),
                ]);
                $tag->save();
                $dbTags[] = $tag->id;
            }

            $cocktail->tags()->attach($dbTags);
        } catch (Throwable $e) {
            $this->log->error('[COCKTAIL_SERVICE] ' . $e->getMessage());
            $this->db->rollBack();

            throw new CocktailException('Error occured while creating a cocktail!', 0, $e);
        }

        $this->db->commit();

        if (count($images) > 0) {
            try {
                $imageModels = Image::findOrFail($images);
                $cocktail->attachImages($imageModels);
            } catch (Throwable $e) {
                $this->log->error('[COCKTAIL_SERVICE] Image attach error. ' . $e->getMessage());

                throw new CocktailException('Error occured while attaching images to cocktail!', 0, $e);
            }
        }

        $this->log->info('[COCKTAIL_SERVICE] Cocktail "' . $name . '" created with id: ' . $cocktail->id);

        // Refresh model for response
        $cocktail->refresh();
        // Upsert scout index
        $cocktail->save();

        return $cocktail;
    }

    /**
     * Update cocktail by id
     *
     * @param int $id
     * @param string $name
     * @param string $instructions
     * @param array<Ingredient> $ingredients
     * @param int $userId
     * @param string|null $description
     * @param string|null $garnish
     * @param string|null $cocktailSource
     * @param array<int> $images
     * @param array<string> $tags
     * @param int|null $glassId
     * @param int|null $cocktailMethodId
     * @return \Kami\Cocktail\Models\Cocktail
     */
    public function updateCocktail(
        int $id,
        string $name,
        string $instructions,
        array $ingredients,
        int $userId,
        ?string $description = null,
        ?string $garnish = null,
        ?string $cocktailSource = null,
        array $images = [],
        array $tags = [],
        ?int $glassId = null,
        ?int $cocktailMethodId = null
    ): Cocktail {
        $this->db->beginTransaction();

        try {
            $cocktail = Cocktail::findOrFail($id);
            $cocktail->name = $name;
            $cocktail->instructions = $instructions;
            $cocktail->description = $description;
            $cocktail->garnish = $garnish;
            $cocktail->source = $cocktailSource;
            if ($cocktail->user_id !== 1) {
                $cocktail->user_id = $userId;
            }
            $cocktail->glass_id = $glassId;
            $cocktail->cocktail_method_id = $cocktailMethodId;
            $cocktail->save();

            // TODO: Implement upsert and delete
            $cocktail->ingredients()->delete();
            foreach ($ingredients as $ingredient) {
                if (!($ingredient instanceof Ingredient)) {
                    $this->log->warning('[COCKTAIL_SERVICE] Ingredient in ingredients array is of wrong type!');
                    continue;
                }

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
            foreach ($tags as $tagName) {
                $tag = Tag::firstOrNew([
                    'name' => trim($tagName),
                ]);
                $tag->save();
                $dbTags[] = $tag->id;
            }

            $cocktail->tags()->sync($dbTags);
        } catch (Throwable $e) {
            $this->log->error('[COCKTAIL_SERVICE] ' . $e->getMessage());
            $this->db->rollBack();

            throw new CocktailException('Error occured while updating a cocktail with id "' . $id . '"!', 0, $e);
        }

        $this->db->commit();

        if (count($images) > 0) {
            // $cocktail->deleteImages();
            try {
                $imageModels = Image::findOrFail($images);
                $cocktail->attachImages($imageModels);
            } catch (Throwable $e) {
                $this->log->error('[COCKTAIL_SERVICE] Image attach error. ' . $e->getMessage());

                throw new CocktailException('Error occured while attaching images to cocktail!', 0, $e);
            }
        }

        $this->log->info('[COCKTAIL_SERVICE] Updated cocktail with id: ' . $cocktail->id);

        // Refresh model for response
        $cocktail->refresh();
        // Upsert scout index
        $cocktail->save();

        return $cocktail;
    }

    /**
     * Return all cocktails that user can create with
     * ingredients in his shelf
     *
     * @param int $userId
     * @return \Illuminate\Support\Collection<string, mixed>
     */
    public function getCocktailsByUserIngredients(int $userId, ?int $limit = null): Collection
    {
        $query = $this->db->table('cocktails AS c')
            ->select('c.id')
            ->join('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'c.id')
            ->join('ingredients AS i', 'i.id', '=', 'ci.ingredient_id')
            ->leftJoin('cocktail_ingredient_substitutes AS cis', 'cis.cocktail_ingredient_id', '=', 'ci.id')
            ->where('optional', false)
            ->whereIn('i.id', function ($query) use ($userId) {
                $query->select('ingredient_id')->from('user_ingredients')->where('user_id', $userId);
            })
            ->orWhereIn('cis.ingredient_id', function ($query) use ($userId) {
                $query->select('ingredient_id')->from('user_ingredients')->where('user_id', $userId);
            })
            ->groupBy('c.id')
            ->havingRaw('COUNT(*) >= (SELECT COUNT(*) FROM cocktail_ingredients WHERE cocktail_id = c.id AND optional = false)');
        
        if ($limit) {
            $query->limit($limit);
        }

        return $query->pluck('id');
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
}
