<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Tag;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Utensil;
use Illuminate\Support\Facades\DB;
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

    /**
     * This is a quick insert method used by importers. It skips calling models directly.
     */
    public function insertFromExternal(object $externalCocktail, Bar $bar, User $user, array $dbGlasses, array $dbMethods, array $dbIngredients): int
    {
        $slug = $externalCocktail->id . '-' . $bar->id;

        $cocktailId = DB::table('cocktails')->insertGetId([
            'slug' => $slug,
            'name' => $externalCocktail->name,
            'instructions' => $externalCocktail->instructions,
            'description' => $externalCocktail->description,
            'garnish' => $externalCocktail->garnish,
            'source' => $externalCocktail->source,
            'abv' => $externalCocktail->abv,
            'created_user_id' => $user->id,
            'glass_id' => $dbGlasses[mb_strtolower($externalCocktail->glass ?? '', 'UTF-8')] ?? null,
            'cocktail_method_id' => $dbMethods[mb_strtolower($externalCocktail->method ?? '', 'UTF-8')] ?? null,
            'bar_id' => $bar->id,
            'created_at' => $externalCocktail->createdAt ?? now(),
            'updated_at' => $externalCocktail->updatedAt,
        ]);

        foreach ($externalCocktail->tags as $tag) {
            $tag = Tag::firstOrCreate([
                'name' => trim($tag),
                'bar_id' => $bar->id,
            ]);
            $tagsToInsert[] = [
                'tag_id' => $tag->id,
                'cocktail_id' => $cocktailId,
            ];
        }

        foreach ($externalCocktail->utensils as $utensil) {
            $utensil = Utensil::firstOrCreate([
                'name' => trim($utensil),
                'bar_id' => $bar->id,
            ]);
            $cocktailUtensilsToInsert[] = [
                'utensil_id' => $utensil->id,
                'cocktail_id' => $cocktailId,
            ];
        }

        $sort = 1;
        foreach ($externalCocktail->ingredients as $cocktailIngredient) {
            $matchedIngredientId = $dbIngredients[mb_strtolower($cocktailIngredient->ingredient->name, 'UTF-8')] ?? null;
            if (!$matchedIngredientId) {
                $this->log->warning(sprintf('Unable to match ingredient "%s" to cocktail "%s"', $cocktailIngredient->ingredient->name, $externalCocktail->name));
                continue;
            }

            $ciId = DB::table('cocktail_ingredients')->insertGetId([
                'cocktail_id' => $cocktailId,
                'ingredient_id' => $matchedIngredientId,
                'amount' => $cocktailIngredient->amount,
                'units' => $cocktailIngredient->units,
                'optional' => $cocktailIngredient->optional,
                'note' => $cocktailIngredient->note,
                'sort' => $sort,
            ]);

            $sort++;

            foreach ($cocktailIngredient->substitutes as $substitute) {
                $matchedSubIngredientId = $dbIngredients[mb_strtolower($substitute->ingredient->name, 'UTF-8')] ?? null;
                if (!$matchedSubIngredientId) {
                    $this->log->warning(sprintf('Unable to match substitute ingredient "%s" to cocktail "%s"', $substitute->ingredient->name, $externalCocktail->name));
                    continue;
                }

                DB::table('cocktail_ingredient_substitutes')->insert([
                    'cocktail_ingredient_id' => $ciId,
                    'ingredient_id' => $matchedSubIngredientId,
                    'amount' => $substitute->amount,
                    'amount_max' => $substitute->amountMax,
                    'units' => $substitute->units,
                    'created_at' => now(),
                    'updated_at' => null,
                ]);
            }
        }

        return $cocktailId;
    }
}
