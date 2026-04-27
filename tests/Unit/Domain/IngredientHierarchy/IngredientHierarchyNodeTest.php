<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\IngredientHierarchy;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyNode;
use BarAssistant\Domain\Ingredient\Exception\IngredientHierarchyException;

final class IngredientHierarchyNodeTest extends TestCase
{
    public function test_create_root_returns_root_node(): void
    {
        $barId = new BarId(77);
        $node = IngredientHierarchyNode::createRoot($barId);

        $this->assertTrue($node->isTransient());
        $this->assertTrue($node->isRoot());
        $this->assertNull($node->getParentId());
        $this->assertSame('', $node->getMaterializedPath()->toString());
    }

    public function test_create_child_computes_path_from_parent(): void
    {
        $barId = new BarId(77);
        $parent = IngredientHierarchyNode::createRoot($barId);
        $parent->setId(new IngredientId(1));

        $child = IngredientHierarchyNode::createChild($barId, $parent);

        $this->assertTrue($child->isTransient());
        $this->assertFalse($child->isRoot());
        $this->assertSame(1, $child->getParentId()->value);
        $this->assertSame('1/', $child->getMaterializedPath()->toString());
    }

    public function test_create_child_rejects_different_bar(): void
    {
        $parent = IngredientHierarchyNode::createRoot(new BarId(1));
        $parent->setId(new IngredientId(1));

        $this->expectException(IngredientHierarchyException::class);
        IngredientHierarchyNode::createChild(new BarId(2), $parent);
    }

    public function test_create_child_rejects_transient_parent(): void
    {
        $parent = IngredientHierarchyNode::createRoot(new BarId(1));

        $this->expectException(IngredientHierarchyException::class);
        IngredientHierarchyNode::createChild(new BarId(1), $parent);
    }

    public function test_set_id_assigns_identity(): void
    {
        $node = IngredientHierarchyNode::createRoot(new BarId(77));
        $node->setId(new IngredientId(42));

        $this->assertFalse($node->isTransient());
        $this->assertSame(42, $node->getId()->value);
    }

    public function test_set_id_rejects_reassignment(): void
    {
        $node = IngredientHierarchyNode::createRoot(new BarId(77));
        $node->setId(new IngredientId(1));

        $this->expectException(IngredientHierarchyException::class);
        $node->setId(new IngredientId(2));
    }

    public function test_change_parent_updates_path(): void
    {
        $barId = new BarId(77);

        $spirits = $this->node($barId, 1, null, '');
        $whiskey = $this->node($barId, 2, $spirits, '1/');
        $scotch = $this->node($barId, 3, $whiskey, '1/2/');

        $scotch->changeParent($spirits);

        $this->assertSame(1, $scotch->getParentId()->value);
        $this->assertSame('1/', $scotch->getMaterializedPath()->toString());
    }

    public function test_change_parent_to_null_makes_root(): void
    {
        $barId = new BarId(77);

        $spirits = $this->node($barId, 1, null, '');
        $whiskey = $this->node($barId, 2, $spirits, '1/');

        $whiskey->changeParent(null);

        $this->assertTrue($whiskey->isRoot());
        $this->assertNull($whiskey->getParentId());
        $this->assertSame('', $whiskey->getMaterializedPath()->toString());
    }

    public function test_change_parent_rejects_transient_node(): void
    {
        $barId = new BarId(77);
        $node = IngredientHierarchyNode::createRoot($barId);
        $parent = $this->node($barId, 1, null, '');

        $this->expectException(IngredientHierarchyException::class);
        $node->changeParent($parent);
    }

    public function test_change_parent_rejects_transient_parent(): void
    {
        $barId = new BarId(77);
        $node = $this->node($barId, 1, null, '');
        $parent = IngredientHierarchyNode::createRoot($barId);

        $this->expectException(IngredientHierarchyException::class);
        $node->changeParent($parent);
    }

    public function test_change_parent_rejects_different_bar(): void
    {
        $node = $this->node(new BarId(1), 1, null, '');
        $parent = $this->node(new BarId(2), 2, null, '');

        $this->expectException(IngredientHierarchyException::class);
        $node->changeParent($parent);
    }

    public function test_change_parent_rejects_self_parent(): void
    {
        $node = $this->node(new BarId(1), 1, null, '');

        $this->expectException(IngredientHierarchyException::class);
        $node->changeParent($node);
    }

    public function test_change_parent_rejects_moving_under_descendant(): void
    {
        $barId = new BarId(1);

        $A = $this->node($barId, 1, null, '');
        $B = $this->node($barId, 2, $A, '1/');
        $C = $this->node($barId, 3, $B, '1/2/');

        $this->expectException(IngredientHierarchyException::class);
        $B->changeParent($C);
    }

    public function test_rebase_path_updates_descendant_paths(): void
    {
        $barId = new BarId(77);

        $genever = $this->node($barId, 100, null, '');
        $whiskey = $this->node($barId, 4, $genever, '100/');
        $scotch = $this->node($barId, 5, $whiskey, '100/4/');
        $islay = $this->node($barId, 6, $scotch, '100/4/5/');
        $ardbeg = $this->node($barId, 7, $islay, '100/4/5/6/');

        // Simulate moving scotch (id=5) from under whiskey (id=4) to under genever (id=100)
        // oldBase = oldPath + scotchId = '100/4/' + '5/' = '100/4/5/'
        // newBase = newPath + scotchId = '100/' + '5/' = '100/5/'
        $oldBase = MaterializedPath::fromString('100/4/5/');
        $newBase = MaterializedPath::fromString('100/5/');

        $scotch->changeParent($genever);
        $islay->rebasePath($oldBase, $newBase);
        $ardbeg->rebasePath($oldBase, $newBase);

        $this->assertSame('100/', $scotch->getMaterializedPath()->toString());
        $this->assertSame('100/5/', $islay->getMaterializedPath()->toString());
        $this->assertSame('100/5/6/', $ardbeg->getMaterializedPath()->toString());
    }

    public function test_is_ancestor_of_detects_ancestor(): void
    {
        $barId = new BarId(1);

        $A = $this->node($barId, 1, null, '');
        $B = $this->node($barId, 2, $A, '1/');
        $C = $this->node($barId, 3, $B, '1/2/');

        // Root is NOT considered an ancestor per MaterializedPath semantics
        $this->assertFalse($A->isAncestorOf($B));
        $this->assertFalse($A->isAncestorOf($C));

        $this->assertTrue($B->isAncestorOf($C));
        $this->assertFalse($C->isAncestorOf($A));
        $this->assertFalse($B->isAncestorOf($A));
    }

    public function test_make_root_delegates_to_change_parent_with_null(): void
    {
        $barId = new BarId(1);
        $parent = $this->node($barId, 1, null, '');
        $child = $this->node($barId, 2, $parent, '1/');

        $child->makeRoot();

        $this->assertTrue($child->isRoot());
        $this->assertNull($child->getParentId());
    }

    public function test_from_persistence_hydrates_node(): void
    {
        $node = IngredientHierarchyNode::fromPersistence(
            barId: new BarId(77),
            id: new IngredientId(5),
            parentId: new IngredientId(1),
            materializedPath: MaterializedPath::fromString('1/'),
        );

        $this->assertFalse($node->isTransient());
        $this->assertSame(5, $node->getId()->value);
        $this->assertSame(1, $node->getParentId()->value);
        $this->assertSame('1/', $node->getMaterializedPath()->toString());
    }

    private function node(BarId $barId, int $id, ?IngredientHierarchyNode $parent, string $path): IngredientHierarchyNode
    {
        return IngredientHierarchyNode::fromPersistence(
            barId: $barId,
            id: new IngredientId($id),
            parentId: $parent?->getId(),
            materializedPath: MaterializedPath::fromString($path),
        );
    }
}
