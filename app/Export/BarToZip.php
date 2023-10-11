<?php

declare(strict_types=1);

namespace Kami\Cocktail\Export;

use ZipArchive;
use Carbon\Carbon;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Exceptions\ExportException;

class BarToZip
{
    public function __construct(
        private readonly LogManager $log,
    ) {
    }

    public function process(array $barIds, ?string $exportPath = null): string
    {
        $version = config('bar-assistant.version');
        $meta = [
            'version' => $version,
            'date' => Carbon::now()->toJSON(),
            'called_from' => __CLASS__,
        ];

        $zip = new ZipArchive();

        $export = [
            'bars' => DB::table('bars')->whereIn('id', $barIds)->get()->toArray(),
            'bar_memberships' => DB::table('bar_memberships')->whereIn('bar_id', $barIds)->get()->toArray(),
            'glasses' => DB::table('glasses')->whereIn('bar_id', $barIds)->get()->toArray(),
            'cocktail_methods' => DB::table('cocktail_methods')->whereIn('bar_id', $barIds)->get()->toArray(),
            'ingredients' => DB::table('ingredients')->whereIn('bar_id', $barIds)->get()->toArray(),
            'cocktails' => DB::table('cocktails')->whereIn('bar_id', $barIds)->get()->toArray(),
            'tags' => DB::table('tags')->whereIn('bar_id', $barIds)->get()->toArray(),
            'utensils' => DB::table('utensils')->whereIn('bar_id', $barIds)->get()->toArray(),
            'ingredient_categories' => DB::table('ingredient_categories')->whereIn('bar_id', $barIds)->get()->toArray(),
            'cocktail_ingredient_substitutes' => DB::table('cocktail_ingredient_substitutes')
                ->select('cocktail_ingredient_substitutes.*')
                ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredient_substitutes.ingredient_id')
                ->whereIn('ingredients.bar_id', $barIds)
                ->get()
                ->toArray(),
            'user_ingredients' => DB::table('user_ingredients')
                ->select('user_ingredients.*')
                ->join('bar_memberships', 'user_ingredients.bar_membership_id', '=', 'bar_memberships.bar_id')
                ->whereIn('bar_memberships.bar_id', $barIds)
                ->get()
                ->toArray(),
            'user_shopping_lists' => DB::table('user_shopping_lists')
                ->select('user_shopping_lists.*')
                ->join('bar_memberships', 'user_shopping_lists.bar_membership_id', '=', 'bar_memberships.bar_id')
                ->whereIn('bar_memberships.bar_id', $barIds)
                ->get()
                ->toArray(),
            'users' => DB::table('users')
                ->select('users.id', 'users.email', 'users.email_verified_at', 'users.created_at', 'users.updated_at')
                ->join('bar_memberships', 'bar_memberships.user_id', '=', 'users.id')
                ->whereIn('bar_memberships.bar_id', $barIds)
                ->get()
                ->toArray(),
            'cocktail_tag' => DB::table('cocktail_tag')
                ->select('cocktail_tag.*')
                ->join('cocktails', 'cocktails.id', '=', 'cocktail_tag.cocktail_id')
                ->whereIn('cocktails.bar_id', $barIds)
                ->get()
                ->toArray(),
            'cocktail_ingredients' => DB::table('cocktail_ingredients')
                ->select('cocktail_ingredients.*')
                ->join('cocktails', 'cocktails.id', '=', 'cocktail_ingredients.cocktail_id')
                ->whereIn('bar_id', $barIds)
                ->get()
                ->toArray(),
            'cocktail_favorites' => DB::table('cocktail_favorites')
                ->select('cocktail_favorites.*')
                ->join('bar_memberships', 'cocktail_favorites.bar_membership_id', '=', 'bar_memberships.bar_id')
                ->whereIn('bar_memberships.bar_id', $barIds)
                ->get()
                ->toArray(),
            'collections' => DB::table('collections')
                ->select('collections.*')
                ->join('bar_memberships', 'collections.bar_membership_id', '=', 'bar_memberships.bar_id')
                ->whereIn('bar_memberships.bar_id', $barIds)
                ->get()
                ->toArray(),
            'ratings' => DB::table('ratings')
                ->select('ratings.*')
                ->join('cocktails', 'ratings.rateable_id', '=', 'cocktails.id')
                ->whereIn('cocktails.bar_id', $barIds)
                ->get()
                ->toArray(),
            'notes' => DB::table('notes')
                ->select('notes.*')
                ->join('bar_memberships', 'bar_memberships.user_id', '=', 'notes.user_id')
                ->whereIn('bar_memberships.bar_id', $barIds)
                ->get()
                ->toArray(),
            'cocktail_utensil' => DB::table('cocktail_utensil')
                ->select('cocktail_utensil.*')
                ->join('cocktails', 'cocktails.id', '=', 'cocktail_utensil.cocktail_id')
                ->whereIn('cocktails.bar_id', $barIds)
                ->get()
                ->toArray(),
            'images' => array_merge(
                DB::table('images')
                    ->select('images.*')
                    ->join('cocktails', 'cocktails.id', '=', 'images.imageable_id')
                    ->whereIn('cocktails.bar_id', $barIds)
                    ->where('images.imageable_type', \Kami\Cocktail\Models\Cocktail::class)
                    ->get()
                    ->toArray(),
                DB::table('images')
                    ->select('images.*')
                    ->join('ingredients', 'ingredients.id', '=', 'images.imageable_id')
                    ->whereIn('ingredients.bar_id', $barIds)
                    ->where('images.imageable_type', \Kami\Cocktail\Models\Ingredient::class)
                    ->get()
                    ->toArray(),
            ),
        ];

        $filename = storage_path(sprintf('bar-assistant/backups/%s_%s_%s.zip', Carbon::now()->format('Ymdhi'), 'bass', implode('-', $barIds)));
        if ($exportPath) {
            $filename = $exportPath;
        }

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            $message = sprintf('Error creating zip archive with filepath "%s"', $filename);
            $this->log->error($message);

            throw new ExportException($message);
        }

        $zip->addGlob(storage_path('bar-assistant/uploads/*/{' . implode(',', $barIds) . '}/*'), GLOB_BRACE, ['remove_path' => storage_path('bar-assistant')]);

        if ($metaContent = json_encode($meta)) {
            $zip->addFromString('_meta.json', $metaContent);
        }

        if ($tables = json_encode($export)) {
            $zip->addFromString('tables.json', $tables);
        }

        $zip->close();

        return $filename;
    }
}
