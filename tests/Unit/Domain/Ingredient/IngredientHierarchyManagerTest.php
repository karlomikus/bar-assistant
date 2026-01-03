<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientHierarchyManager;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use PHPUnit\Framework\TestCase;

final class IngredientHierarchyManagerTest extends TestCase
{
    public function testChangeParent(): void
    {
        $repo = $this->createStub(IngredientRepository::class);

        $service = new IngredientHierarchyManager($repo);

        $barId = new BarId(77);

        $spirits = new Ingredient($barId);
        $spirits->updateDetails('Spirits')->setId(new IngredientId(1));
        $genever = new Ingredient($barId);
        $genever->updateDetails('Genever')->setId(new IngredientId(2));
        $gin = new Ingredient($barId);
        $gin->updateDetails('Gin')->setId(new IngredientId(3));
        $whiskey = new Ingredient($barId);
        $whiskey->updateDetails('Whiskey')->setId(new IngredientId(4));
        $scotch = new Ingredient($barId);
        $scotch->updateDetails('Scotch')->setId(new IngredientId(5));
        $islayScotch = new Ingredient($barId);
        $islayScotch->updateDetails('Islay Scotch')->setId(new IngredientId(6));
        $speysideScotch = new Ingredient($barId);
        $speysideScotch->updateDetails('Speyside Scotch')->setId(new IngredientId(7));
        $ardbeg = new Ingredient($barId);
        $ardbeg->updateDetails('Ardbeg')->setId(new IngredientId(8));

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
        $this->assertSame(1, $genever->getParentIngredientId()->id);
        $this->assertSame('1/2/', $gin->getMaterializedPath()->toString());
        $this->assertSame(2, $gin->getParentIngredientId()->id);
        $this->assertSame('1/', $whiskey->getMaterializedPath()->toString());
        $this->assertSame(1, $whiskey->getParentIngredientId()->id);
        $this->assertSame('1/4/', $scotch->getMaterializedPath()->toString());
        $this->assertSame(4, $scotch->getParentIngredientId()->id);
        $this->assertSame('1/4/5/', $islayScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $islayScotch->getParentIngredientId()->id);
        $this->assertSame('1/4/5/', $speysideScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $speysideScotch->getParentIngredientId()->id);
        $this->assertSame('1/4/5/6/', $ardbeg->getMaterializedPath()->toString());
        $this->assertSame(6, $ardbeg->getParentIngredientId()->id);

        $repo->method('findDescendants')->willReturn([$scotch, $speysideScotch, $islayScotch, $ardbeg]);
        $service->changeParent($whiskey, $genever);

        $this->assertSame('1/2/', $whiskey->getMaterializedPath()->toString());
        $this->assertSame(2, $whiskey->getParentIngredientId()->id);
        $this->assertSame('1/2/4/', $scotch->getMaterializedPath()->toString());
        $this->assertSame(4, $scotch->getParentIngredientId()->id);
        $this->assertSame('1/2/4/5/', $speysideScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $speysideScotch->getParentIngredientId()->id);
        $this->assertSame('1/2/4/5/', $islayScotch->getMaterializedPath()->toString());
        $this->assertSame(5, $islayScotch->getParentIngredientId()->id);
        $this->assertSame('1/2/4/5/6/', $ardbeg->getMaterializedPath()->toString());
        $this->assertSame(6, $ardbeg->getParentIngredientId()->id);
    }
}
