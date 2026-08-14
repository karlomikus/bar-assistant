<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\IngredientHierarchy;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use Tests\Infrastructure\InMemoryIngredientHierarchyRepository;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyNode;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyManager;
use BarAssistant\Domain\Ingredient\Exception\IngredientHierarchyException;

final class IngredientHierarchyManagerTest extends TestCase
{
    public function test_change_parent_updates_all_descendants(): void
    {
        $repo = $this->createRepositoryWithTree();
        $manager = new IngredientHierarchyManager($repo);

        $spirits = $repo->findById(new IngredientId(1));
        $genever = $repo->findById(new IngredientId(2));
        $gin = $repo->findById(new IngredientId(3));
        $whiskey = $repo->findById(new IngredientId(4));
        $scotch = $repo->findById(new IngredientId(5));
        $islayScotch = $repo->findById(new IngredientId(6));
        $speysideScotch = $repo->findById(new IngredientId(7));
        $ardbeg = $repo->findById(new IngredientId(8));

        $this->assertSame('', $spirits->getMaterializedPath()->toString());
        $this->assertNull($spirits->getParentId());
        $this->assertSame('1/', $genever->getMaterializedPath()->toString());
        $this->assertSame(1, $genever->getParentId()->value);
        $this->assertSame('1/2/', $gin->getMaterializedPath()->toString());
        $this->assertSame(2, $gin->getParentId()->value);
        $this->assertSame('1/', $whiskey->getMaterializedPath()->toString());
        $this->assertSame(1, $whiskey->getParentId()->value);
        $this->assertSame('1/4/', $scotch->getMaterializedPath()->toString());
        $this->assertSame(4, $scotch->getParentId()->value);
        $this->assertSame('1/4/5/', $islayScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $islayScotch->getParentId()->value);
        $this->assertSame('1/4/5/', $speysideScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $speysideScotch->getParentId()->value);
        $this->assertSame('1/4/5/6/', $ardbeg->getMaterializedPath()->toString());
        $this->assertSame(6, $ardbeg->getParentId()->value);

        // Move whiskey under genever
        $manager->changeParent($whiskey, $genever);

        $this->assertSame('1/2/', $whiskey->getMaterializedPath()->toString());
        $this->assertSame(2, $whiskey->getParentId()->value);
        $this->assertSame('1/2/4/', $scotch->getMaterializedPath()->toString());
        $this->assertSame(4, $scotch->getParentId()->value);
        $this->assertSame('1/2/4/5/', $speysideScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $speysideScotch->getParentId()->value);
        $this->assertSame('1/2/4/5/', $islayScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $islayScotch->getParentId()->value);
        $this->assertSame('1/2/4/5/6/', $ardbeg->getMaterializedPath()->toString());
        $this->assertSame(6, $ardbeg->getParentId()->value);
    }

    public function test_make_root_updates_all_descendants(): void
    {
        $repo = $this->createRepositoryWithTree();
        $manager = new IngredientHierarchyManager($repo);

        $whiskey = $repo->findById(new IngredientId(4));
        $scotch = $repo->findById(new IngredientId(5));
        $islayScotch = $repo->findById(new IngredientId(6));

        $manager->makeRoot($whiskey);

        $this->assertTrue($whiskey->isRoot());
        $this->assertNull($whiskey->getParentId());
        $this->assertSame('4/', $scotch->getMaterializedPath()->toString());
        $this->assertSame('4/5/', $islayScotch->getMaterializedPath()->toString());
    }

    public function test_change_parent_rejects_move_under_descendant(): void
    {
        $repo = new InMemoryIngredientHierarchyRepository();
        $manager = new IngredientHierarchyManager($repo);

        $barId = new BarId(77);

        $parent = $this->node($barId, 1, null, '');
        $child = $this->node($barId, 2, $parent, '1/');
        $descendant = $this->node($barId, 3, $child, '1/2/');

        $repo->save($parent);
        $repo->save($child);
        $repo->save($descendant);

        $this->expectException(IngredientHierarchyException::class);
        $manager->changeParent($child, $descendant);
    }

    public function test_change_parent_rejects_self_parent(): void
    {
        $repo = new InMemoryIngredientHierarchyRepository();
        $manager = new IngredientHierarchyManager($repo);

        $barId = new BarId(77);
        $node = $this->node($barId, 1, null, '');
        $repo->save($node);

        $this->expectException(IngredientHierarchyException::class);
        $manager->changeParent($node, $node);
    }

    public function test_change_parent_rejects_transient_node(): void
    {
        $repo = new InMemoryIngredientHierarchyRepository();
        $manager = new IngredientHierarchyManager($repo);

        $barId = new BarId(77);
        $transient = IngredientHierarchyNode::createRoot($barId);
        $parent = $this->node($barId, 1, null, '');
        $repo->save($parent);

        $this->expectException(IngredientHierarchyException::class);
        $manager->changeParent($transient, $parent);
    }

    public function test_change_parent_rejects_transient_parent(): void
    {
        $repo = new InMemoryIngredientHierarchyRepository();
        $manager = new IngredientHierarchyManager($repo);

        $barId = new BarId(77);
        $node = $this->node($barId, 1, null, '');
        $transient = IngredientHierarchyNode::createRoot($barId);
        $repo->save($node);

        $this->expectException(IngredientHierarchyException::class);
        $manager->changeParent($node, $transient);
    }

    public function test_change_parent_rejects_different_bars(): void
    {
        $repo = new InMemoryIngredientHierarchyRepository();
        $manager = new IngredientHierarchyManager($repo);

        $node = $this->node(new BarId(1), 1, null, '');
        $differentBar = $this->node(new BarId(2), 2, null, '');
        $repo->save($node);
        $repo->save($differentBar);

        $this->expectException(IngredientHierarchyException::class);
        $manager->changeParent($node, $differentBar);
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

    private function createRepositoryWithTree(): InMemoryIngredientHierarchyRepository
    {
        $repo = new InMemoryIngredientHierarchyRepository();
        $barId = new BarId(77);

        $spirits = $this->node($barId, 1, null, '');
        $genever = $this->node($barId, 2, $spirits, '1/');
        $gin = $this->node($barId, 3, $genever, '1/2/');
        $whiskey = $this->node($barId, 4, $spirits, '1/');
        $scotch = $this->node($barId, 5, $whiskey, '1/4/');
        $islayScotch = $this->node($barId, 6, $scotch, '1/4/5/');
        $speysideScotch = $this->node($barId, 7, $scotch, '1/4/5/'); // same depth path is ok for siblings
        $ardbeg = $this->node($barId, 8, $islayScotch, '1/4/5/6/');

        $repo->save($spirits);
        $repo->save($genever);
        $repo->save($gin);
        $repo->save($whiskey);
        $repo->save($scotch);
        $repo->save($islayScotch);
        $repo->save($speysideScotch);
        $repo->save($ardbeg);

        return $repo;
    }
}
