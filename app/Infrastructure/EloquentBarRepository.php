<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\Bar;
use BarAssistant\Domain\Bar\BarId;
use Illuminate\Support\Facades\DB;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Authors;
use Kami\Cocktail\Models\BarIngredient;
use Kami\Cocktail\Models\Bar as ModelBar;
use BarAssistant\Domain\Bar\BarRepository;
use BarAssistant\Domain\Bar\BarSettings;
use BarAssistant\Domain\Bar\BarStatus;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Bar\IngredientInventoryItem;
use BarAssistant\Domain\Bar\IngredientInventoryStatus;
use BarAssistant\Domain\Common\Slug;

final class EloquentBarRepository implements BarRepository
{
    public function save(Bar $bar): Bar
    {
        $model = ModelBar::findOrNew($bar->getId()?->value);

        $model->name = (string) $bar->getName();
        $model->subtitle = $bar->getSubtitle();
        $model->description = $bar->getDescription();
        $model->is_public = $bar->isPublic();
        $model->status = match ($bar->getStatus()) {
            BarStatus::Active => 'active',
            BarStatus::Provisioning => 'provisioning',
            BarStatus::Deactivated => 'deactivated',
        };
        $model->created_user_id = $bar->getAuthors()->getCreatedBy()->value;
        // if ($bar->getRecordTimestamps()->wasUpdated()) {
        //     $model->updated_at = $bar->getRecordTimestamps()->getUpdatedAt();
        //     $model->updated_user_id = $bar->getAuthors()->getUpdatedBy()->value;
        // }

        $settings = $model->settings ?? [];
        if ($bar->getDefaultUnits()) {
            $settings['default_units'] = $bar->getDefaultUnits()->value;
        }
        if ($bar->getDefaultCurrency()) {
            $settings['default_currency'] = $bar->getDefaultCurrency()->getCurrencyCode();
        }
        $model->settings = $settings;

        $model->save();

        // Get current ingredient IDs from the domain model
        $inStockIngredientIds = array_map(
            fn (IngredientInventoryItem $item) => $item->ingredientId->value,
            $bar->getInStockIngredients()
        );

        // Remove ingredients that are no longer in stock
        $model->shelfIngredients()
            ->whereNotIn('ingredient_id', $inStockIngredientIds)
            ->delete();

        // Get existing ingredient IDs to avoid duplicates
        $existingIngredientIds = $model->shelfIngredients()
            ->pluck('ingredient_id')
            ->toArray();

        // Add new ingredients
        $newBarIngredients = [];
        foreach ($bar->getInStockIngredients() as $inStockIngredient) {
            if (!in_array($inStockIngredient->ingredientId->value, $existingIngredientIds, true)) {
                $barIngredientModel = new BarIngredient();
                $barIngredientModel->ingredient_id = $inStockIngredient->ingredientId->value;
                $newBarIngredients[] = $barIngredientModel;
            }
        }

        if (count($newBarIngredients) > 0) {
            $model->shelfIngredients()->saveMany($newBarIngredients);
        }

        return self::map($model);
    }

    public function findById(BarId $id): ?Bar
    {
        $model = ModelBar::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function delete(BarId $id): void
    {
        $model = ModelBar::find($id->value);
        if ($model === null) {
            return;
        }

        $model->delete();
    }

    private static function map(ModelBar $model): Bar
    {
        $model->load('shelfIngredients.ingredient');

        $barShelfIngredientIds = $model->shelfIngredients->pluck('ingredient_id')->toArray();

        $placeholders = implode(',', array_fill(0, count($barShelfIngredientIds), '?'));
        $complexIngredients = DB::table('complex_ingredients')
            ->select('main_ingredient_id as ingredient_id')
            ->join('ingredients', 'ingredients.id', 'complex_ingredients.main_ingredient_id')
            ->where('ingredients.bar_id', $model->id)
            ->groupBy('main_ingredient_id')
            ->havingRaw('MIN(ingredient_id IN ('.$placeholders.')) = 1', [$barShelfIngredientIds])
            ->get();

        $modelBarSettings = $model->settings ?? [];

        $barSettings = BarSettings::create(
            isInviteCodeEnabled: $model->invite_code !== null,
            defaultUnits: $modelBarSettings['default_units'] ?? null,
            defaultCurrency: $modelBarSettings['default_currency'] ?? null,
        );

        $bar = Bar::create(
            name: Name::fromString($model->name),
            authors: Authors::createdBy(new UserId($model->created_user_id))->updatedBy($model->updated_user_id ? new UserId($model->updated_user_id) : null),
            recordTimestamps: RecordTimestamps::createdAt($model->created_at->toDateTimeImmutable())->updatedAt($model->updated_at?->toDateTimeImmutable()),
            settings: $barSettings,
        )
        ->setId(new BarId($model->id))
        ->setSlug(Slug::fromString($model->slug));

        foreach ($model->shelfIngredients as $barShelfIngredient) {
            $bar->putIngredientInInventory(new IngredientId($barShelfIngredient->ingredient_id), IngredientInventoryStatus::InStock);
        }

        foreach ($complexIngredients as $complexIngredient) {
            $bar->putIngredientInInventory(new IngredientId($complexIngredient->ingredient_id), IngredientInventoryStatus::Makeable);
        }

        return $bar;
    }
}
