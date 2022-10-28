<?php
declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Kami\Cocktail\Models\Tag;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Database\DatabaseManager;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Exceptions\CocktailException;

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
     * @param array $ingredients
     * @param int $userId
     * @param string|null $description
     * @param string|null $garnish
     * @param string|null $cocktailSource
     * @param array<int> $images
     * @param array<string> $tags
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
    ): Cocktail
    {
        $this->db->beginTransaction();

        try {
            $cocktail = new Cocktail();
            $cocktail->name = $name;
            $cocktail->instructions = $instructions;
            $cocktail->description = $description;
            $cocktail->garnish = $garnish;
            $cocktail->source = $cocktailSource;
            $cocktail->user_id = $userId;
            $cocktail->save();

            foreach($ingredients as $ingredient) {
                $cIngredient = new CocktailIngredient();
                $cIngredient->ingredient_id = $ingredient['ingredient_id'];
                $cIngredient->amount = $ingredient['amount'];
                $cIngredient->units = $ingredient['units'];
                $cIngredient->optional = $ingredient['optional'] ?? false;
                $cIngredient->sort = $ingredient['sort'] ?? 0;

                $cocktail->ingredients()->save($cIngredient);
            }

            $dbTags = [];
            foreach($tags as $tagName) {
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
        $cocktail->searchable();

        return $cocktail;
    }

    /**
     * Update cocktail by id
     *
     * @param int $id
     * @param string $name
     * @param string $instructions
     * @param array $ingredients
     * @param int $userId
     * @param string|null $description
     * @param string|null $garnish
     * @param string|null $cocktailSource
     * @param array<int> $images
     * @param array<string> $tags
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
    ): Cocktail
    {
        $this->db->beginTransaction();

        try {
            $cocktail = Cocktail::findOrFail($id);
            $cocktail->name = $name;
            $cocktail->instructions = $instructions;
            $cocktail->description = $description;
            $cocktail->garnish = $garnish;
            $cocktail->source = $cocktailSource;
            $cocktail->user_id = $userId;
            $cocktail->save();

            // TODO: Implement upsert and delete
            $cocktail->ingredients()->delete();
            foreach($ingredients as $ingredient) {
                $cIngredient = new CocktailIngredient();
                $cIngredient->ingredient_id = $ingredient['ingredient_id'];
                $cIngredient->amount = $ingredient['amount'];
                $cIngredient->units = $ingredient['units'];
                $cIngredient->optional = $ingredient['optional'] ?? false;
                $cIngredient->sort = $ingredient['sort'] ?? 0;

                $cocktail->ingredients()->save($cIngredient);
            }

            $dbTags = [];
            foreach($tags as $tagName) {
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
            $cocktail->deleteImages();
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
        $cocktail->searchable();

        return $cocktail;
    }

    /**
     * Return all cocktails that user can create with
     * ingredients in his shelf
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection<\Kami\Cocktail\Models\Cocktail>
     */
    public function getCocktailsByUserIngredients(int $userId)
    {
        // Cocktails with possible ingredients
        // SELECT c.id, c.name, count(*) as total FROM cocktails AS c
        // INNER JOIN cocktail_ingredients AS ci ON ci.cocktail_id = c.id
        // INNER JOIN ingredients AS i ON i.id = ci.ingredient_id
        // WHERE ci.ingredient_id IN (SELECT ingredient_id FROM user_ingredients WHERE user_id = 2)
        // GROUP BY c.id, c.name
        // HAVING total <= (SELECT COUNT(*) FROM user_ingredients WHERE user_id = 2)
        // ORDER BY total DESC
        // LIMIT 10;

        // Cocktails strictly available
        // https://stackoverflow.com/questions/19930070/mysql-query-to-select-all-except-something
        // SELECT c.*
        // FROM cocktails c
        // JOIN cocktail_ingredients ci ON ci.cocktail_id = c.id
        // JOIN ingredients i ON i.id = ci.ingredient_id
        // GROUP
        //     BY c.id
        // HAVING SUM(CASE WHEN i.id IN (SELECT ingredient_id FROM user_ingredients WHERE user_id = 2) THEN 1 ELSE 0 END) = COUNT(*);

        $cocktailIds = $this->db->table('cocktails AS c')
            ->select('c.id')
            ->join('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'c.id')
            ->join('ingredients AS i', 'i.id', '=', 'ci.ingredient_id')
            ->groupBy('c.id')
            ->havingRaw('SUM(CASE WHEN i.id IN (SELECT ingredient_id FROM user_ingredients WHERE user_id = ?) THEN 1 ELSE 0 END) = COUNT(*)', [$userId])
            ->pluck('id');

        return Cocktail::find($cocktailIds);
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
