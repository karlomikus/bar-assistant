<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientAncestorPath;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use BarAssistant\Domain\User\UserId;
use PHPUnit\Framework\TestCase;

final class IngredientAncestorPathTest extends TestCase
{
    public function test_creates_path_from_ingredient_and_ancestors(): void
    {
        $barId = new BarId(1);
        $userId = new UserId(1);

        $spirits = new Ingredient($barId, 'Spirits', $userId);
        $spirits->setId(new IngredientId(1));

        $whiskey = new Ingredient($barId, 'Whiskey', $userId, materializedPath: MaterializedPath::fromString('1/'));
        $whiskey->setId(new IngredientId(2));

        $scotch = new Ingredient($barId, 'Scotch', $userId, materializedPath: MaterializedPath::fromString('1/2/'));
        $scotch->setId(new IngredientId(3));

        $path = IngredientAncestorPath::from($scotch, [$spirits, $whiskey]);

        $this->assertSame($scotch, $path->getIngredient());
        $this->assertCount(2, $path->getAncestors());
        $this->assertSame($spirits, $path->getAncestors()[0]);
        $this->assertSame($whiskey, $path->getAncestors()[1]);
    }

    public function test_get_full_path_includes_ingredient_itself(): void
    {
        $barId = new BarId(1);
        $userId = new UserId(1);

        $spirits = new Ingredient($barId, 'Spirits', $userId);
        $spirits->setId(new IngredientId(1));

        $whiskey = new Ingredient($barId, 'Whiskey', $userId, materializedPath: MaterializedPath::fromString('1/'));
        $whiskey->setId(new IngredientId(2));

        $scotch = new Ingredient($barId, 'Scotch', $userId, materializedPath: MaterializedPath::fromString('1/2/'));
        $scotch->setId(new IngredientId(3));

        $path = IngredientAncestorPath::from($scotch, [$spirits, $whiskey]);
        $fullPath = $path->getFullPath();

        $this->assertCount(3, $fullPath);
        $this->assertSame($spirits, $fullPath[0]);
        $this->assertSame($whiskey, $fullPath[1]);
        $this->assertSame($scotch, $fullPath[2]);
    }

    public function test_to_string_path_formats_correctly_with_default_separator(): void
    {
        $barId = new BarId(1);
        $userId = new UserId(1);

        $spirits = new Ingredient($barId, 'Spirits', $userId);
        $spirits->setId(new IngredientId(1));

        $whiskey = new Ingredient($barId, 'Whiskey', $userId, materializedPath: MaterializedPath::fromString('1/'));
        $whiskey->setId(new IngredientId(2));

        $scotch = new Ingredient($barId, 'Scotch', $userId, materializedPath: MaterializedPath::fromString('1/2/'));
        $scotch->setId(new IngredientId(3));

        $islayScotch = new Ingredient($barId, 'Islay Scotch', $userId, materializedPath: MaterializedPath::fromString('1/2/3/'));
        $islayScotch->setId(new IngredientId(4));

        $path = IngredientAncestorPath::from($islayScotch, [$spirits, $whiskey, $scotch]);

        $this->assertSame('Spirits > Whiskey > Scotch > Islay Scotch', $path->toStringPath());
    }

    public function test_to_string_path_formats_correctly_with_custom_separator(): void
    {
        $barId = new BarId(1);
        $userId = new UserId(1);

        $spirits = new Ingredient($barId, 'Spirits', $userId);
        $spirits->setId(new IngredientId(1));

        $whiskey = new Ingredient($barId, 'Whiskey', $userId, materializedPath: MaterializedPath::fromString('1/'));
        $whiskey->setId(new IngredientId(2));

        $path = IngredientAncestorPath::from($whiskey, [$spirits]);

        $this->assertSame('Spirits / Whiskey', $path->toStringPath(' / '));
        $this->assertSame('Spirits :: Whiskey', $path->toStringPath(' :: '));
        $this->assertSame('Spirits|Whiskey', $path->toStringPath('|'));
    }

    public function test_root_ingredient_has_empty_ancestor_list(): void
    {
        $barId = new BarId(1);
        $userId = new UserId(1);

        $spirits = new Ingredient($barId, 'Spirits', $userId);
        $spirits->setId(new IngredientId(1));

        $path = IngredientAncestorPath::from($spirits, []);

        $this->assertCount(0, $path->getAncestors());
        $this->assertCount(1, $path->getFullPath());
        $this->assertSame('Spirits', $path->toStringPath());
    }

    public function test_ancestor_path_preserves_order(): void
    {
        $barId = new BarId(1);
        $userId = new UserId(1);

        $root = new Ingredient($barId, 'Root', $userId);
        $root->setId(new IngredientId(1));

        $level1 = new Ingredient($barId, 'Level 1', $userId, materializedPath: MaterializedPath::fromString('1/'));
        $level1->setId(new IngredientId(2));

        $level2 = new Ingredient($barId, 'Level 2', $userId, materializedPath: MaterializedPath::fromString('1/2/'));
        $level2->setId(new IngredientId(3));

        $level3 = new Ingredient($barId, 'Level 3', $userId, materializedPath: MaterializedPath::fromString('1/2/3/'));
        $level3->setId(new IngredientId(4));

        $path = IngredientAncestorPath::from($level3, [$root, $level1, $level2]);

        $ancestors = $path->getAncestors();
        $this->assertSame('Root', $ancestors[0]->getName());
        $this->assertSame('Level 1', $ancestors[1]->getName());
        $this->assertSame('Level 2', $ancestors[2]->getName());
        $this->assertSame('Root > Level 1 > Level 2 > Level 3', $path->toStringPath());
    }

    public function test_path_with_single_ancestor(): void
    {
        $barId = new BarId(1);
        $userId = new UserId(1);

        $parent = new Ingredient($barId, 'Parent', $userId);
        $parent->setId(new IngredientId(1));

        $child = new Ingredient($barId, 'Child', $userId, materializedPath: MaterializedPath::fromString('1/'));
        $child->setId(new IngredientId(2));

        $path = IngredientAncestorPath::from($child, [$parent]);

        $this->assertCount(1, $path->getAncestors());
        $this->assertSame('Parent > Child', $path->toStringPath());
    }
}
