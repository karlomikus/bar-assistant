<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use RuntimeException;
use BarAssistant\Domain\Bar\BarId;
use Illuminate\Support\Facades\DB;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use Kami\Cocktail\Models\Ingredient as ModelIngredient;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyNode;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyRepository;

final class EloquentIngredientHierarchyRepository implements IngredientHierarchyRepository
{
    public function findById(IngredientId $id): ?IngredientHierarchyNode
    {
        $model = ModelIngredient::query()
            ->select(['id', 'bar_id', 'parent_ingredient_id', 'materialized_path'])
            ->find($id->value);

        if ($model === null) {
            return null;
        }

        return $this->mapNode($model);
    }

    public function findDescendants(IngredientId $id, BarId $barId): array
    {
        $rootNode = $this->findById($id);
        if ($rootNode === null) {
            return [];
        }

        $searchPrefix = $rootNode->getMaterializedPath()->append($id)->toString();

        $models = ModelIngredient::query()
            ->select(['id', 'bar_id', 'parent_ingredient_id', 'materialized_path'])
            ->where('bar_id', $barId->value)
            ->where('materialized_path', 'like', $searchPrefix . '%')
            ->get();

        return array_map($this->mapNode(...), $models->all());
    }

    public function findAncestors(IngredientId $id, BarId $barId): array
    {
        $node = $this->findById($id);
        if ($node === null) {
            return [];
        }

        $ancestorIds = $node->getMaterializedPath()->getAncestorIds();
        if (count($ancestorIds) === 0) {
            return [];
        }

        $models = ModelIngredient::query()
            ->select(['id', 'bar_id', 'parent_ingredient_id', 'materialized_path'])
            ->where('bar_id', $barId->value)
            ->whereIn('id', array_map(static fn (IngredientId $ancestorId): int => $ancestorId->value, $ancestorIds))
            ->get();

        return array_map($this->mapNode(...), $models->all());
    }

    public function save(IngredientHierarchyNode $node): void
    {
        $id = $node->getId();
        if ($id === null) {
            throw new RuntimeException('Cannot persist transient ingredient hierarchy node');
        }

        ModelIngredient::query()->whereKey($id->value)->update([
            'parent_ingredient_id' => $node->getParentId()?->value,
            'materialized_path' => $node->getMaterializedPath()->toString() ?: null,
        ]);
    }

    public function saveHierarchyMove(IngredientHierarchyNode $movedNode, array $descendants): void
    {
        $nodes = [$movedNode, ...$descendants];
        $table = (new ModelIngredient())->getTable();

        $materializedPathCase = 'CASE id';
        $parentIdCase = 'CASE id';
        $materializedPathBindings = [];
        $parentIdBindings = [];
        $ids = [];

        foreach ($nodes as $node) {
            $nodeId = $node->getId();
            if ($nodeId === null) {
                throw new RuntimeException('Cannot persist transient ingredient hierarchy node');
            }

            $ids[] = $nodeId->value;

            $materializedPathCase .= ' WHEN ? THEN ?';
            $materializedPathBindings[] = $nodeId->value;
            $materializedPathBindings[] = $node->getMaterializedPath()->toString() ?: null;

            $parentIdCase .= ' WHEN ? THEN ?';
            $parentIdBindings[] = $nodeId->value;
            $parentIdBindings[] = $node->getParentId()?->value;
        }

        $materializedPathCase .= ' END';
        $parentIdCase .= ' END';

        $bindings = [
            ...$materializedPathBindings,
            ...$parentIdBindings,
            $movedNode->getBarId()->value,
            ...$ids,
        ];

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        DB::transaction(function () use ($table, $materializedPathCase, $parentIdCase, $placeholders, $bindings): void {
            DB::update(
                "UPDATE {$table} SET materialized_path = {$materializedPathCase}, parent_ingredient_id = {$parentIdCase} WHERE bar_id = ? AND id IN ({$placeholders})",
                $bindings,
            );
        });
    }

    private function mapNode(ModelIngredient $model): IngredientHierarchyNode
    {
        return IngredientHierarchyNode::fromPersistence(
            barId: new BarId($model->bar_id),
            id: new IngredientId($model->id),
            parentId: $model->parent_ingredient_id !== null ? new IngredientId($model->parent_ingredient_id) : null,
            materializedPath: MaterializedPath::fromString($model->materialized_path),
        );
    }
}
