<?php

declare(strict_types=1);

namespace Kami\Cocktail\Import;

use Symfony\Component\Uid\Ulid;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserRoleEnum;
use Kami\Cocktail\Search\SearchActionsAdapter;

class FromVersion2
{
    public function __construct(private readonly SearchActionsAdapter $search)
    {
    }

    public function process(): void
    {
        $backupDB = DB::connection('sqlite_import_from_v2');
        $oldUploads = 'bar-assistant/backupv2/uploads';

        DB::transaction(function () use ($backupDB, $oldUploads) {
            $newUsers = [];
            $newAdminId = null;
            $oldUsers = $backupDB->table('users')->get();
            foreach ($oldUsers as $oldUser) {
                if ($oldUser->id === 1) {
                    continue;
                }

                $userId = DB::table('users')->insertGetId([
                    'name' => $oldUser->name,
                    'email' => $oldUser->email,
                    'email_verified_at' => $oldUser->email_verified_at,
                    'password' => Hash::needsRehash($oldUser->password) ? null : $oldUser->password,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($oldUser->is_admin && $newAdminId === null) {
                    $newAdminId = $userId;
                }

                $newUsers[$oldUser->id] = $userId;
            }

            // Create a new bar
            $barId = DB::table('bars')->insertGetId([
                'name' => 'My migrated bar',
                'description' => 'Bar with data migrated from Bar Assistant v2',
                'created_user_id' => $newAdminId,
                'created_at' => now(),
                'invite_code' => (string) new Ulid(),
            ]);

            // Add search key to new bar
            DB::table('bars')->where('id', $barId)->update([
                'search_driver_api_key' => $this->search->getActions()->getBarSearchApiKey($barId),
            ]);

            // Add new users to bar
            $barMemberships = [];
            foreach ($newUsers as $newUserId) {
                $barMemberships[$newUserId] = DB::table('bar_memberships')->insertGetId([
                    'bar_id' => $barId,
                    'user_id' => $newUserId,
                    'user_role_id' => $newUserId === $newAdminId ? UserRoleEnum::Admin->value : UserRoleEnum::General->value,
                ]);
            }

            // Migrate glasses
            $newGlasses = [];
            $oldGlasses = $backupDB->table('glasses')->get();
            foreach ($oldGlasses as $row) {
                $newGlasses[$row->id] = DB::table('glasses')->insertGetId([
                    'bar_id' => $barId,
                    'name' => $row->name,
                    'description' => $row->description,
                    'created_user_id' => $newAdminId,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }

            // Migrate methods
            $newMethods = [];
            $oldMethods = $backupDB->table('cocktail_methods')->get();
            foreach ($oldMethods as $row) {
                $newMethods[$row->id] = DB::table('cocktail_methods')->insertGetId([
                    'bar_id' => $barId,
                    'name' => $row->name,
                    'description' => $row->description,
                    'dilution_percentage' => $row->dilution_percentage,
                    'created_user_id' => $newAdminId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Migrate utensils
            $newUtensils = [];
            $oldUtensils = $backupDB->table('utensils')->get();
            foreach ($oldUtensils as $row) {
                $newUtensils[$row->id] = DB::table('utensils')->insertGetId([
                    'bar_id' => $barId,
                    'name' => $row->name,
                    'description' => $row->description,
                    'created_user_id' => $newAdminId,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }

            // Migrate categories
            $newCategories = [];
            $oldCategories = $backupDB->table('ingredient_categories')->get();
            foreach ($oldCategories as $row) {
                $newCategories[$row->id] = DB::table('ingredient_categories')->insertGetId([
                    'bar_id' => $barId,
                    'name' => $row->name,
                    'description' => $row->description,
                    'created_user_id' => $newAdminId,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }

            // Migrate tags
            $newTags = [];
            $oldTags = $backupDB->table('tags')->get();
            foreach ($oldTags as $row) {
                $newTags[$row->id] = DB::table('tags')->insertGetId([
                    'bar_id' => $barId,
                    'name' => $row->name,
                ]);
            }

            // Migrate ingredients
            $newIngredients = [];
            $oldIngredients = $backupDB->table('ingredients')->get();
            foreach ($oldIngredients as $row) {
                $newIngredients[$row->id] = DB::table('ingredients')->insertGetId([
                    'bar_id' => $barId,
                    'slug' => $row->slug . '-' . $barId,
                    'name' => $row->name,
                    'ingredient_category_id' => $newCategories[$row->ingredient_category_id] ?? null,
                    'strength' => $row->strength,
                    'description' => $row->description,
                    'origin' => $row->origin,
                    'color' => $row->color,
                    'created_user_id' => $newUsers[$row->user_id] ?? $newAdminId,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
            $oldIngredientsWithParent = $backupDB->table('ingredients')->whereNotNull('parent_ingredient_id')->get();
            foreach ($oldIngredientsWithParent as $row) {
                DB::table('ingredients')
                    ->where('id', $newIngredients[$row->id])
                    ->update([
                        'parent_ingredient_id' => $newIngredients[$row->parent_ingredient_id]
                    ]);
            }

            // Migrate cocktails
            $newCocktails = [];
            $newCocktailIngredients = [];
            $oldCocktails = $backupDB->table('cocktails')->get();
            foreach ($oldCocktails as $row) {
                $newCocktails[$row->id] = DB::table('cocktails')->insertGetId([
                    'bar_id' => $barId,
                    'slug' => $row->slug . '-' . $barId,
                    'name' => $row->name,
                    'instructions' => $row->instructions,
                    'description' => $row->description,
                    'garnish' => $row->garnish,
                    'source' => $row->source,
                    'abv' => $row->abv,
                    'public_id' => $row->public_id,
                    'public_at' => $row->public_at,
                    'public_expires_at' => $row->public_expires_at,
                    'glass_id' => $newGlasses[$row->glass_id] ?? null,
                    'cocktail_method_id' => $newMethods[$row->cocktail_method_id] ?? null,
                    'created_user_id' => $newUsers[$row->user_id] ?? $newAdminId,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                $oldCocktailIngredients = $backupDB->table('cocktail_ingredients')->where('cocktail_id', $row->id)->get();
                foreach ($oldCocktailIngredients as $oldCocktailIngredientRow) {
                    $newCocktailIngredients[] = [
                        'cocktail_id' => $newCocktails[$row->id],
                        'ingredient_id' => $newIngredients[$oldCocktailIngredientRow->ingredient_id],
                        'amount' => $oldCocktailIngredientRow->amount,
                        'units' => $oldCocktailIngredientRow->units,
                        'optional' => $oldCocktailIngredientRow->optional,
                        'sort' => $oldCocktailIngredientRow->sort,
                    ];
                }
            }
            DB::table('cocktail_ingredients')->insert($newCocktailIngredients);

            // Migrate cocktail tags
            $newCocktailTags = [];
            $oldCocktailTags = $backupDB->table('cocktail_tag')->get();
            foreach ($oldCocktailTags as $row) {
                $newCocktailTags[] = [
                    'cocktail_id' => $newCocktails[$row->cocktail_id],
                    'tag_id' => $newTags[$row->tag_id],
                ];
            }
            DB::table('cocktail_tag')->insert($newCocktailTags);

            // Migrate cocktail favorites
            $newCocktailFavorites = [];
            $oldCocktailFavorites = $backupDB->table('cocktail_favorites')->get();
            foreach ($oldCocktailFavorites as $row) {
                $newCocktailFavorites[] = [
                    'bar_membership_id' => $barMemberships[$newUsers[$row->user_id]],
                    'cocktail_id' => $newCocktails[$row->cocktail_id],
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('cocktail_favorites')->insert($newCocktailFavorites);

            // Migrate shelf ingredients
            $newShelfIngredients = [];
            $oldShelfIngredients = $backupDB->table('user_ingredients')->get();
            foreach ($oldShelfIngredients as $row) {
                $newShelfIngredients[] = [
                    'bar_membership_id' => $barMemberships[$newUsers[$row->user_id]],
                    'ingredient_id' => $newIngredients[$row->ingredient_id],
                ];
            }
            DB::table('user_ingredients')->insert($newShelfIngredients);

            // Migrate shopping list
            $newList = [];
            $oldList = $backupDB->table('user_shopping_lists')->get();
            foreach ($oldList as $row) {
                $newList[] = [
                    'bar_membership_id' => $barMemberships[$newUsers[$row->user_id]],
                    'ingredient_id' => $newIngredients[$row->ingredient_id],
                ];
            }
            DB::table('user_shopping_lists')->insert($newList);

            // Migrate ratings
            $newRatings = [];
            $oldRatings = $backupDB->table('ratings')->get();
            foreach ($oldRatings as $row) {
                $newRatings[] = [
                    'rateable_type' => $row->rateable_type,
                    'rateable_id' => $newCocktails[$row->rateable_id],
                    'user_id' => $newUsers[$row->user_id],
                    'rating' => $row->rating,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('ratings')->insert($newRatings);

            // Migrate notes
            $newNotes = [];
            $oldNotes = $backupDB->table('notes')->get();
            foreach ($oldNotes as $row) {
                $newNotes[] = [
                    'noteable_type' => $row->noteable_type,
                    'noteable_id' => $newCocktails[$row->noteable_id],
                    'user_id' => $newUsers[$row->user_id],
                    'note' => $row->note,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('notes')->insert($newNotes);

            // Migrate collections
            $newCollections = [];
            $oldCollections = $backupDB->table('collections')->get();
            foreach ($oldCollections as $row) {
                $newCollections[$row->id] = DB::table('collections')->insertGetId([
                    'bar_membership_id' => $barMemberships[$newUsers[$row->user_id]],
                    'name' => $row->name,
                    'description' => $row->description,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
            $newCollectionCocktails = [];
            $oldCollectionCocktails = $backupDB->table('collections_cocktails')->get();
            foreach ($oldCollectionCocktails as $row) {
                $newCollectionCocktails[] = [
                    'collection_id' => $newCollections[$row->collection_id],
                    'cocktail_id' => $newCocktails[$row->cocktail_id],
                ];
            }
            DB::table('collections_cocktails')->insert($newCollectionCocktails);

            // Move images
            $newImages = [];
            $oldImages = $backupDB->table('images')->get();
            foreach ($oldImages as $row) {
                $filepath = $row->file_path;
                if (str_starts_with($row->file_path, 'ingredients')) {
                    $filepath = str_replace('ingredients/', 'ingredients/' . $barId . '/', $row->file_path);
                }
                if (str_starts_with($row->file_path, 'cocktails')) {
                    $filepath = str_replace('cocktails/', 'cocktails/' . $barId . '/', $row->file_path);
                }

                $imageableId = $row->imageable_id;
                if ($row->imageable_type === \Kami\Cocktail\Models\Ingredient::class) {
                    $imageableId = $newIngredients[$row->imageable_id];
                }
                if ($row->imageable_type === \Kami\Cocktail\Models\Cocktail::class) {
                    $imageableId = $newCocktails[$row->imageable_id];
                }

                $newImages[$row->id] = DB::table('images')->insertGetId([
                    'imageable_type' => $row->imageable_type,
                    'imageable_id' => $imageableId,
                    'file_path' => $filepath,
                    'file_extension' => $row->file_extension,
                    'copyright' => $row->copyright,
                    'placeholder_hash' => $row->placeholder_hash,
                    'sort' => $row->sort,
                    'created_user_id' => $newUsers[$row->user_id] ?? $newAdminId,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }

            File::move(storage_path($oldUploads . '/cocktails'), storage_path('bar-assistant/uploads/cocktails/' . $barId));
            File::move(storage_path($oldUploads . '/ingredients'), storage_path('bar-assistant/uploads/ingredients/' . $barId));
            File::move(storage_path($oldUploads . '/temp'), storage_path('bar-assistant/uploads/temp'));

            /** @phpstan-ignore-next-line */
            Ingredient::where('bar_id', $barId)->searchable();
            /** @phpstan-ignore-next-line */
            Cocktail::where('bar_id', $barId)->searchable();
        });
    }
}
