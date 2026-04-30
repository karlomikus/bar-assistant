<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use RuntimeException;
use Illuminate\Support\Facades\DB;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Bar\MemberInventory;
use BarAssistant\Domain\Bar\MemberInventoryId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use Kami\Cocktail\Models\MemberInventoryIngredient;
use BarAssistant\Domain\Bar\IngredientInventoryItem;
use BarAssistant\Domain\Bar\IngredientInventoryStatus;
use BarAssistant\Domain\Bar\MemberInventoryRepository;
use Kami\Cocktail\Models\MemberInventory as ModelMemberInventory;

final class EloquentMemberInventoryRepository implements MemberInventoryRepository
{
    public function save(MemberInventory $memberInventory): MemberInventory
    {
        return DB::transaction(function () use ($memberInventory): MemberInventory {
            $model = ModelMemberInventory::findOrNew($memberInventory->getId()?->value);

            $model->bar_membership_id = $memberInventory->getMemberId()->value;
            $model->name = (string) $memberInventory->getName();
            $model->created_at = $memberInventory->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');
            $model->created_user_id = $memberInventory->getAuthors()->getCreatedBy()->value;

            if ($memberInventory->getRecordTimestamps()->wasUpdated()) {
                $model->updated_at = $memberInventory->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
            }

            if ($memberInventory->getAuthors()->isUpdated() && $memberInventory->getAuthors()->getUpdatedBy() !== null) {
                $model->updated_user_id = $memberInventory->getAuthors()->getUpdatedBy()->value;
            }

            $model->save();

            $inStockIngredientIds = array_map(
                static fn (IngredientInventoryItem $item): int => $item->ingredientId->value,
                $memberInventory->getIngredients(),
            );

            if ($inStockIngredientIds === []) {
                $model->inventoryIngredients()->delete();

                return self::map($model->fresh('inventoryIngredients') ?? $model->load('inventoryIngredients'));
            }

            $model->inventoryIngredients()
                ->whereNotIn('ingredient_id', $inStockIngredientIds)
                ->delete();

            $existingIngredientIds = $model->inventoryIngredients()
                ->pluck('ingredient_id')
                ->all();

            $newMemberInventoryIngredients = [];
            foreach ($memberInventory->getIngredients() as $inventoryIngredient) {
                if (!in_array($inventoryIngredient->ingredientId->value, $existingIngredientIds, true)) {
                    $memberInventoryIngredient = new MemberInventoryIngredient();
                    $memberInventoryIngredient->ingredient_id = $inventoryIngredient->ingredientId->value;
                    $newMemberInventoryIngredients[] = $memberInventoryIngredient;
                }
            }

            if (count($newMemberInventoryIngredients) > 0) {
                $model->inventoryIngredients()->saveMany($newMemberInventoryIngredients);
            }

            return self::map($model->fresh('inventoryIngredients') ?? $model->load('inventoryIngredients'));
        });
    }

    public function delete(MemberInventory $memberInventory): void
    {
        ModelMemberInventory::destroy($memberInventory->getId()?->value);
    }

    public function findById(MemberInventoryId $memberInventoryId): ?MemberInventory
    {
        $model = ModelMemberInventory::with('inventoryIngredients')->find($memberInventoryId->value);
        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function findByMemberId(MemberId $memberId): array
    {
        return ModelMemberInventory::with('inventoryIngredients')
            ->where('bar_membership_id', $memberId->value)
            ->orderBy('name')
            ->get()
            ->map(static fn (ModelMemberInventory $model): MemberInventory => self::map($model))
            ->all();
    }

    public function existsWithName(MemberId $memberId, Name $name, ?MemberInventoryId $excludeInventoryId = null): bool
    {
        $query = ModelMemberInventory::query()
            ->where('bar_membership_id', $memberId->value)
            ->where('name', (string) $name);

        if ($excludeInventoryId !== null) {
            $query->whereKeyNot($excludeInventoryId->value);
        }

        return $query->exists();
    }

    private static function map(ModelMemberInventory $model): MemberInventory
    {
        $createdAt = $model->created_at?->toDateTimeImmutable();
        if ($createdAt === null) {
            throw new RuntimeException('Cannot map member inventory without a creation timestamp.');
        }

        $ingredients = [];
        foreach ($model->inventoryIngredients as $modelInventoryIngredient) {
            $ingredients[] = new IngredientInventoryItem(
                ingredientId: new IngredientId($modelInventoryIngredient->ingredient_id),
                ingredientStatus: IngredientInventoryStatus::InStock,
            );
        }

        return MemberInventory::create(
            memberId: new MemberId($model->bar_membership_id),
            name: Name::fromString($model->name),
            authors: Authors::createdBy(new UserId($model->created_user_id))
                ->updatedBy($model->updated_user_id ? new UserId($model->updated_user_id) : null),
            recordTimestamps: RecordTimestamps::createdAt($createdAt)
                ->updatedAt($model->updated_at?->toDateTimeImmutable()),
            ingredients: $ingredients,
        )->setId(new MemberInventoryId($model->id));
    }
}
