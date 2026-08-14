<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use BarAssistant\Domain\Bar\BarId;
use Kami\Cocktail\Models\BarMembership;
use BarAssistant\Domain\Ingredient\IngredientId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Models\Ingredient as ModelIngredient;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyNode;
use Kami\Cocktail\Infrastructure\EloquentIngredientHierarchyRepository;

final class EloquentIngredientHierarchyRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_node_by_id(): void
    {
        $membership = $this->setupBarMembership();
        $spirits = $this->createIngredient($membership, 'Spirits');

        $repository = new EloquentIngredientHierarchyRepository();
        $node = $repository->findById(new IngredientId($spirits->id));

        $this->assertNotNull($node);
        $this->assertSame($spirits->id, $node->getId()?->value);
        $this->assertSame($membership->bar_id, $node->getBarId()->value);
        $this->assertNull($node->getParentId());
        $this->assertSame('', $node->getMaterializedPath()->toString());
    }

    public function test_it_finds_descendants(): void
    {
        $membership = $this->setupBarMembership();
        $tree = $this->createTree($membership);

        $repository = new EloquentIngredientHierarchyRepository();
        $descendants = $repository->findDescendants(
            new IngredientId($tree['spirits']->id),
            new BarId($membership->bar_id),
        );

        $this->assertCount(5, $descendants);
        $this->assertEqualsCanonicalizing(
            [
                $tree['genever']->id,
                $tree['gin']->id,
                $tree['whiskey']->id,
                $tree['scotch']->id,
                $tree['ardbeg']->id,
            ],
            array_values(array_map(
                static fn (IngredientHierarchyNode $node): int => $node->getId()?->value ?? 0,
                $descendants,
            )),
        );
    }

    public function test_it_finds_ancestors(): void
    {
        $membership = $this->setupBarMembership();
        $tree = $this->createTree($membership);

        $repository = new EloquentIngredientHierarchyRepository();
        $ancestors = $repository->findAncestors(
            new IngredientId($tree['ardbeg']->id),
            new BarId($membership->bar_id),
        );

        $this->assertCount(3, $ancestors);
        $this->assertEqualsCanonicalizing(
            [
                $tree['spirits']->id,
                $tree['whiskey']->id,
                $tree['scotch']->id,
            ],
            array_values(array_map(
                static fn (IngredientHierarchyNode $node): int => $node->getId()?->value ?? 0,
                $ancestors,
            )),
        );
    }

    public function test_it_saves_node_hierarchy_state(): void
    {
        $membership = $this->setupBarMembership();
        $spirits = $this->createIngredient($membership, 'Spirits');
        $liqueurs = $this->createIngredient($membership, 'Liqueurs');
        $gin = $this->createIngredient($membership, 'Gin', parent: $spirits, materializedPath: $spirits->id . '/');

        $repository = new EloquentIngredientHierarchyRepository();
        $node = $repository->findById(new IngredientId($gin->id));
        $newParent = $repository->findById(new IngredientId($liqueurs->id));

        $this->assertNotNull($node);
        $this->assertNotNull($newParent);

        $node->changeParent($newParent);

        $repository->save($node);

        $this->assertDatabaseHas('ingredients', [
            'id' => $gin->id,
            'parent_ingredient_id' => $liqueurs->id,
            'materialized_path' => $liqueurs->id . '/',
        ]);
    }

    public function test_it_saves_hierarchy_move_for_moved_node_and_descendants(): void
    {
        $membership = $this->setupBarMembership();
        $tree = $this->createTree($membership);

        $repository = new EloquentIngredientHierarchyRepository();
        $movedNode = $repository->findById(new IngredientId($tree['whiskey']->id));
        $newParent = $repository->findById(new IngredientId($tree['genever']->id));
        $descendants = $repository->findDescendants(new IngredientId($tree['whiskey']->id), new BarId($membership->bar_id));

        $this->assertNotNull($movedNode);
        $this->assertNotNull($newParent);

        $movedNodeId = $movedNode->getId();
        $this->assertNotNull($movedNodeId);

        $oldBase = $movedNode->getMaterializedPath()->append($movedNodeId);

        $movedNode->changeParent($newParent);

        $newBase = $movedNode->getMaterializedPath()->append($movedNodeId);

        foreach ($descendants as $descendant) {
            $descendant->rebasePath(oldBase: $oldBase, newBase: $newBase);
        }

        $repository->saveHierarchyMove($movedNode, $descendants);

        $this->assertDatabaseHas('ingredients', [
            'id' => $tree['whiskey']->id,
            'parent_ingredient_id' => $tree['genever']->id,
            'materialized_path' => $tree['spirits']->id . '/' . $tree['genever']->id . '/',
        ]);
        $this->assertDatabaseHas('ingredients', [
            'id' => $tree['scotch']->id,
            'parent_ingredient_id' => $tree['whiskey']->id,
            'materialized_path' => $tree['spirits']->id . '/' . $tree['genever']->id . '/' . $tree['whiskey']->id . '/',
        ]);
        $this->assertDatabaseHas('ingredients', [
            'id' => $tree['ardbeg']->id,
            'parent_ingredient_id' => $tree['scotch']->id,
            'materialized_path' => $tree['spirits']->id . '/' . $tree['genever']->id . '/' . $tree['whiskey']->id . '/' . $tree['scotch']->id . '/',
        ]);
    }

    /**
     * @return array{spirits: ModelIngredient, genever: ModelIngredient, gin: ModelIngredient, whiskey: ModelIngredient, scotch: ModelIngredient, ardbeg: ModelIngredient}
     */
    private function createTree(BarMembership $membership): array
    {
        $spirits = $this->createIngredient($membership, 'Spirits');
        $genever = $this->createIngredient($membership, 'Genever', parent: $spirits, materializedPath: $spirits->id . '/');
        $gin = $this->createIngredient($membership, 'Gin', parent: $genever, materializedPath: $spirits->id . '/' . $genever->id . '/');
        $whiskey = $this->createIngredient($membership, 'Whiskey', parent: $spirits, materializedPath: $spirits->id . '/');
        $scotch = $this->createIngredient($membership, 'Scotch', parent: $whiskey, materializedPath: $spirits->id . '/' . $whiskey->id . '/');
        $ardbeg = $this->createIngredient($membership, 'Ardbeg', parent: $scotch, materializedPath: $spirits->id . '/' . $whiskey->id . '/' . $scotch->id . '/');

        return compact('spirits', 'genever', 'gin', 'whiskey', 'scotch', 'ardbeg');
    }

    private function createIngredient(
        BarMembership $membership,
        string $name,
        ?ModelIngredient $parent = null,
        ?string $materializedPath = null,
    ): ModelIngredient {
        return ModelIngredient::factory()
            ->recycle($membership->bar)
            ->create([
                'name' => $name,
                'parent_ingredient_id' => $parent?->id,
                'materialized_path' => $materializedPath,
            ]);
    }
}
