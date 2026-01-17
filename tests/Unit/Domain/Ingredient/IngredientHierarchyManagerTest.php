<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\Exception\IngredientHierarchyException;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientHierarchyManager;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\User\UserId;
use PHPUnit\Framework\TestCase;

final class IngredientHierarchyManagerTest extends TestCase
{
    public function test_change_parent_updates_all_descendants(): void
    {
        $repo = $this->createStub(IngredientRepository::class);

        $service = new IngredientHierarchyManager($repo);

        $barId = new BarId(77);
        $userId = new UserId(1);
        $authors = Authors::createdBy($userId);
        $timestamps = RecordTimestamps::createdNow();

        $spirits = new Ingredient($barId, Name::fromString('Spirits'), $authors, $timestamps);
        $spirits->setId(new IngredientId(1));
        $genever = new Ingredient($barId, Name::fromString('Genever'), $authors, $timestamps);
        $genever->setId(new IngredientId(2));
        $gin = new Ingredient($barId, Name::fromString('Gin'), $authors, $timestamps);
        $gin->setId(new IngredientId(3));
        $whiskey = new Ingredient($barId, Name::fromString('Whiskey'), $authors, $timestamps);
        $whiskey->setId(new IngredientId(4));
        $scotch = new Ingredient($barId, Name::fromString('Scotch'), $authors, $timestamps);
        $scotch->setId(new IngredientId(5));
        $islayScotch = new Ingredient($barId, Name::fromString('Islay Scotch'), $authors, $timestamps);
        $islayScotch->setId(new IngredientId(6));
        $speysideScotch = new Ingredient($barId, Name::fromString('Speyside Scotch'), $authors, $timestamps);
        $speysideScotch->setId(new IngredientId(7));
        $ardbeg = new Ingredient($barId, Name::fromString('Ardbeg'), $authors, $timestamps);
        $ardbeg->setId(new IngredientId(8));

        // Adding leaf nodes
        $service->changeParent($genever, $spirits);
        $service->changeParent($gin, $genever);
        $service->changeParent($whiskey, $spirits);
        $service->changeParent($scotch, $whiskey);
        $service->changeParent($islayScotch, $scotch);
        $service->changeParent($speysideScotch, $scotch);
        $service->changeParent($ardbeg, $islayScotch);

        $this->assertSame('', $spirits->getMaterializedPath()->toString());
        $this->assertSame(null, $spirits->getParentIngredientId());
        $this->assertSame('1/', $genever->getMaterializedPath()->toString());
        $this->assertSame(1, $genever->getParentIngredientId()->value);
        $this->assertSame('1/2/', $gin->getMaterializedPath()->toString());
        $this->assertSame(2, $gin->getParentIngredientId()->value);
        $this->assertSame('1/', $whiskey->getMaterializedPath()->toString());
        $this->assertSame(1, $whiskey->getParentIngredientId()->value);
        $this->assertSame('1/4/', $scotch->getMaterializedPath()->toString());
        $this->assertSame(4, $scotch->getParentIngredientId()->value);
        $this->assertSame('1/4/5/', $islayScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $islayScotch->getParentIngredientId()->value);
        $this->assertSame('1/4/5/', $speysideScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $speysideScotch->getParentIngredientId()->value);
        $this->assertSame('1/4/5/6/', $ardbeg->getMaterializedPath()->toString());
        $this->assertSame(6, $ardbeg->getParentIngredientId()->value);

        $repo->method('findDescendants')->willReturn([$scotch, $speysideScotch, $islayScotch, $ardbeg]);
        $service->changeParent($whiskey, $genever);

        $this->assertSame('1/2/', $whiskey->getMaterializedPath()->toString());
        $this->assertSame(2, $whiskey->getParentIngredientId()->value);
        $this->assertSame('1/2/4/', $scotch->getMaterializedPath()->toString());
        $this->assertSame(4, $scotch->getParentIngredientId()->value);
        $this->assertSame('1/2/4/5/', $speysideScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $speysideScotch->getParentIngredientId()->value);
        $this->assertSame('1/2/4/5/', $islayScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $islayScotch->getParentIngredientId()->value);
        $this->assertSame('1/2/4/5/6/', $ardbeg->getMaterializedPath()->toString());
        $this->assertSame(6, $ardbeg->getParentIngredientId()->value);
    }

    public function test_change_parent_rejects_move_under_descendant(): void
    {
        $repo = $this->createStub(IngredientRepository::class);

        $service = new IngredientHierarchyManager($repo);

        $barId = new BarId(77);
        $userId = new UserId(1);
        $authors = Authors::createdBy($userId);
        $timestamps = RecordTimestamps::createdNow();

        $parent = new Ingredient($barId, Name::fromString('Parent'), $authors, $timestamps);
        $parent->setId(new IngredientId(1));
        $child = new Ingredient($barId, Name::fromString('Direct child'), $authors, $timestamps);
        $child->setId(new IngredientId(2));
        $desc = new Ingredient($barId, Name::fromString('Descendant'), $authors, $timestamps);
        $desc->setId(new IngredientId(3));

        $child = $service->changeParent($child, $parent);
        $desc = $service->changeParent($desc, $child);

        $this->expectException(IngredientHierarchyException::class);
        $service->changeParent($child, $desc);
    }

    public function test_change_parent_rejects_its_own_parent(): void
    {
        $repo = $this->createStub(IngredientRepository::class);

        $service = new IngredientHierarchyManager($repo);

        $barId = new BarId(77);
        $userId = new UserId(1);
        $authors = Authors::createdBy($userId);
        $timestamps = RecordTimestamps::createdNow();

        $parent = new Ingredient($barId, Name::fromString('Parent'), $authors, $timestamps);
        $parent->setId(new IngredientId(1));

        $this->expectException(IngredientHierarchyException::class);
        $service->changeParent($parent, $parent);
    }
}
