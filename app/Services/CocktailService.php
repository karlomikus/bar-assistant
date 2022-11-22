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
     * @param array $ingredients
     * @param int $userId
     * @param string|null $description
     * @param string|null $garnish
     * @param string|null $cocktailSource
     * @param array<int> $images
     * @param array<string> $tags
     * @param int|null $glassId
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
        ?int $glassId = null
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
            $cocktail->glass_id = $glassId;
            $cocktail->save();

            foreach($ingredients as $ingredient) {
                $cIngredient = new CocktailIngredient();
                $cIngredient->ingredient_id = $ingredient['ingredient_id'];
                $cIngredient->amount = $ingredient['amount'];
                $cIngredient->units = $ingredient['units'];
                $cIngredient->optional = $ingredient['optional'] ?? false;
                $cIngredient->sort = $ingredient['sort'] ?? 0;

                $cocktail->ingredients()->save($cIngredient);

                // Substitutes
                foreach ($ingredient['substitutes'] ?? [] as $subId) {
                    $substitute = new CocktailIngredientSubstitute();
                    $substitute->ingredient_id = $subId;
                    $cIngredient->substitutes()->save($substitute);
                }
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
        $cocktail->save();

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
     * @param int|null $glassId
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
            if ($cocktail->user_id !== 1) {
                $cocktail->user_id = $userId;
            }
            $cocktail->glass_id = $glassId;
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

                // Substitutes
                $cIngredient->substitutes()->delete();
                foreach ($ingredient['substitutes'] ?? [] as $subId) {
                    $substitute = new CocktailIngredientSubstitute();
                    $substitute->ingredient_id = $subId;
                    $cIngredient->substitutes()->save($substitute);
                }
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
        $cocktail->save();

        return $cocktail;
    }

    /**
     * Return all cocktails that user can create with
     * ingredients in his shelf
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection<\Kami\Cocktail\Models\Cocktail>
     */
    public function getCocktailsByUserIngredients(int $userId): Collection
    {
        // https://stackoverflow.com/questions/19930070/mysql-query-to-select-all-except-something
        $cocktailIds = $this->db->table('cocktails AS c')
            ->select('c.id')
            ->join('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'c.id')
            ->where('ci.optional', false)
            ->groupBy('c.id')
            ->havingRaw('SUM(CASE WHEN ci.ingredient_id IN (SELECT ingredient_id FROM user_ingredients WHERE user_id = ?) THEN 1 ELSE 0 END) = COUNT(*)', [$userId])
            ->pluck('id');
        
        // Programatically find cocktails that match your ingredients with possible substituted ingredients.
        // This is currently probably not really performant
        $userIngredients = $this->db->table('user_ingredients')->select('ingredient_id')->where('user_id', $userId)->pluck('ingredient_id'); // TODO: extract, and reuse
        $possibleCocktailsWithSubstitutes = Cocktail::select('cocktails.*')
            ->join('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'cocktails.id')
            ->join('cocktail_ingredient_substitutes AS cis', 'cis.cocktail_ingredient_id', '=', 'ci.id')
            ->join('user_ingredients AS ui', 'ui.ingredient_id', '=', 'cis.ingredient_id')
            ->where('ui.user_id', $userId)
            ->get();

        $subCocktails = [];
        foreach ($possibleCocktailsWithSubstitutes as $cocktail) {
            $ingredientsCount = 0;
            foreach ($cocktail->ingredients as $cocktailIngredient) {
                if ($userIngredients->contains($cocktailIngredient->ingredient_id)) { // User has original ingredient
                    $ingredientsCount++;
                } elseif ($userIngredients->intersect($cocktailIngredient->substitutes->pluck('ingredient_id'))->count() > 0) { // User has one of the substitiute ingredients
                    $ingredientsCount++;
                }
            }
            if ($ingredientsCount === $cocktail->ingredients->count()) { // User can make this cocktail
                $subCocktails[] = $cocktail->id;
            }
        }

        return Cocktail::find(array_merge($cocktailIds->toArray(), $subCocktails));
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
