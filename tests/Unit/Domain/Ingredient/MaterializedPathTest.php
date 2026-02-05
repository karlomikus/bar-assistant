<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ingredient;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\MaterializedPath;

final class MaterializedPathTest extends TestCase
{
    public function test_root_creates_empty_path(): void
    {
        $path = MaterializedPath::root();

        $this->assertTrue($path->isRoot());
        $this->assertSame('', $path->toString());
        $this->assertSame(0, $path->getDepth());
        $this->assertEmpty($path->getAncestorIds());
        $this->assertNull($path->getParentId());
    }

    public function test_from_string_with_null_creates_root(): void
    {
        $path = MaterializedPath::fromString(null);

        $this->assertTrue($path->isRoot());
        $this->assertSame('', $path->toString());
    }

    public function test_from_string_with_empty_string_creates_root(): void
    {
        $path = MaterializedPath::fromString('');

        $this->assertTrue($path->isRoot());
        $this->assertSame('', $path->toString());
    }

    public function test_from_string_parses_single_id(): void
    {
        $path = MaterializedPath::fromString('1/');

        $this->assertFalse($path->isRoot());
        $this->assertSame('1/', $path->toString());
        $this->assertSame(1, $path->getDepth());
        $this->assertCount(1, $path->getAncestorIds());
        $this->assertTrue($path->getAncestorIds()[0]->equals(new IngredientId(1)));
        $this->assertTrue($path->getParentId()->equals(new IngredientId(1)));
    }

    public function test_from_string_parses_multiple_ids(): void
    {
        $path = MaterializedPath::fromString('1/2/3/');

        $this->assertFalse($path->isRoot());
        $this->assertSame('1/2/3/', $path->toString());
        $this->assertSame(3, $path->getDepth());
        $this->assertCount(3, $path->getAncestorIds());
        $this->assertTrue($path->getParentId()->equals(new IngredientId(3)));
    }

    public function test_from_string_handles_path_without_trailing_separator(): void
    {
        $path = MaterializedPath::fromString('1/2/3');

        $this->assertSame('1/2/3/', $path->toString());
        $this->assertSame(3, $path->getDepth());
    }

    public function test_append_adds_id_to_path(): void
    {
        $rootPath = MaterializedPath::root();
        $newPath = $rootPath->append(new IngredientId(1));

        $this->assertFalse($newPath->isRoot());
        $this->assertSame('1/', $newPath->toString());
        $this->assertSame(1, $newPath->getDepth());
    }

    public function test_append_preserves_immutability(): void
    {
        $rootPath = MaterializedPath::root();
        $newPath = $rootPath->append(new IngredientId(1));

        $this->assertTrue($rootPath->isRoot());
        $this->assertFalse($newPath->isRoot());
        $this->assertNotSame($rootPath, $newPath);
    }

    public function test_append_can_chain_multiple_ids(): void
    {
        $path = MaterializedPath::root()
            ->append(new IngredientId(1))
            ->append(new IngredientId(2))
            ->append(new IngredientId(3));

        $this->assertSame('1/2/3/', $path->toString());
        $this->assertSame(3, $path->getDepth());
    }

    public function test_append_throws_exception_when_max_depth_exceeded(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ingredient has too many descendants, max depth is 10');

        $path = MaterializedPath::root();
        for ($i = 1; $i <= 11; $i++) {
            $path = $path->append(new IngredientId($i));
        }
    }

    public function test_get_ancestor_ids_returns_all_ids_in_order(): void
    {
        $path = MaterializedPath::fromString('1/2/3/');
        $ancestorIds = $path->getAncestorIds();

        $this->assertCount(3, $ancestorIds);
        $this->assertTrue($ancestorIds[0]->equals(new IngredientId(1)));
        $this->assertTrue($ancestorIds[1]->equals(new IngredientId(2)));
        $this->assertTrue($ancestorIds[2]->equals(new IngredientId(3)));
    }

    public function test_get_parent_id_returns_last_id(): void
    {
        $path = MaterializedPath::fromString('1/2/3/');

        $this->assertTrue($path->getParentId()->equals(new IngredientId(3)));
    }

    public function test_get_parent_id_returns_null_for_root(): void
    {
        $path = MaterializedPath::root();

        $this->assertNull($path->getParentId());
    }

    public function test_is_ancestor_of_returns_true_for_direct_child(): void
    {
        $parent = MaterializedPath::fromString('1/');
        $child = MaterializedPath::fromString('1/2/');

        $this->assertTrue($parent->isAncestorOf($child));
    }

    public function test_is_ancestor_of_returns_true_for_deep_descendant(): void
    {
        $ancestor = MaterializedPath::fromString('1/');
        $descendant = MaterializedPath::fromString('1/2/3/4/');

        $this->assertTrue($ancestor->isAncestorOf($descendant));
    }

    public function test_is_ancestor_of_returns_false_for_sibling(): void
    {
        $path1 = MaterializedPath::fromString('1/2/');
        $path2 = MaterializedPath::fromString('1/3/');

        $this->assertFalse($path1->isAncestorOf($path2));
    }

    public function test_is_ancestor_of_returns_false_for_root(): void
    {
        $root = MaterializedPath::root();
        $other = MaterializedPath::fromString('1/2/');

        $this->assertFalse($root->isAncestorOf($other));
    }

    public function test_is_ancestor_of_returns_false_for_unrelated_paths(): void
    {
        $path1 = MaterializedPath::fromString('1/2/');
        $path2 = MaterializedPath::fromString('3/4/');

        $this->assertFalse($path1->isAncestorOf($path2));
    }

    public function test_is_descendant_of_returns_true_for_parent(): void
    {
        $child = MaterializedPath::fromString('1/2/');
        $parent = MaterializedPath::fromString('1/');

        $this->assertTrue($child->isDescendantOf($parent));
    }

    public function test_is_descendant_of_returns_true_for_deep_ancestor(): void
    {
        $descendant = MaterializedPath::fromString('1/2/3/4/');
        $ancestor = MaterializedPath::fromString('1/');

        $this->assertTrue($descendant->isDescendantOf($ancestor));
    }

    public function test_is_descendant_of_returns_false_for_sibling(): void
    {
        $path1 = MaterializedPath::fromString('1/2/');
        $path2 = MaterializedPath::fromString('1/3/');

        $this->assertFalse($path1->isDescendantOf($path2));
    }

    public function test_get_relative_path_removes_common_prefix(): void
    {
        $fullPath = MaterializedPath::fromString('1/2/3/4/');
        $basePath = MaterializedPath::fromString('1/2/');
        $relativePath = $fullPath->getRelativePath($basePath);

        $this->assertSame('3/4/', $relativePath->toString());
        $this->assertSame(2, $relativePath->getDepth());
    }

    public function test_get_relative_path_with_root_base_returns_full_path(): void
    {
        $fullPath = MaterializedPath::fromString('1/2/3/');
        $basePath = MaterializedPath::root();
        $relativePath = $fullPath->getRelativePath($basePath);

        $this->assertSame('1/2/3/', $relativePath->toString());
    }

    public function test_get_relative_path_with_no_common_prefix_returns_full_path(): void
    {
        $fullPath = MaterializedPath::fromString('3/4/5/');
        $basePath = MaterializedPath::fromString('1/2/');
        $relativePath = $fullPath->getRelativePath($basePath);

        $this->assertSame('3/4/5/', $relativePath->toString());
    }

    public function test_get_relative_path_with_same_path_returns_root(): void
    {
        $fullPath = MaterializedPath::fromString('1/2/3/');
        $basePath = MaterializedPath::fromString('1/2/3/');
        $relativePath = $fullPath->getRelativePath($basePath);

        $this->assertTrue($relativePath->isRoot());
        $this->assertSame('', $relativePath->toString());
    }

    public function test_get_relative_path_with_partial_match_stops_at_mismatch(): void
    {
        $fullPath = MaterializedPath::fromString('1/2/3/4/');
        $basePath = MaterializedPath::fromString('1/5/');
        $relativePath = $fullPath->getRelativePath($basePath);

        $this->assertSame('2/3/4/', $relativePath->toString());
    }

    public function test_equals_returns_true_for_identical_paths(): void
    {
        $path1 = MaterializedPath::fromString('1/2/3/');
        $path2 = MaterializedPath::fromString('1/2/3/');

        $this->assertTrue($path1->equals($path2));
    }

    public function test_equals_returns_true_for_two_roots(): void
    {
        $path1 = MaterializedPath::root();
        $path2 = MaterializedPath::root();

        $this->assertTrue($path1->equals($path2));
    }

    public function test_equals_returns_false_for_different_paths(): void
    {
        $path1 = MaterializedPath::fromString('1/2/3/');
        $path2 = MaterializedPath::fromString('1/2/4/');

        $this->assertFalse($path1->equals($path2));
    }

    public function test_equals_returns_false_for_different_depths(): void
    {
        $path1 = MaterializedPath::fromString('1/2/');
        $path2 = MaterializedPath::fromString('1/2/3/');

        $this->assertFalse($path1->equals($path2));
    }

    public function test_depth_calculation_is_accurate(): void
    {
        $this->assertSame(0, MaterializedPath::root()->getDepth());
        $this->assertSame(1, MaterializedPath::fromString('1/')->getDepth());
        $this->assertSame(5, MaterializedPath::fromString('1/2/3/4/5/')->getDepth());
        $this->assertSame(10, MaterializedPath::fromString('1/2/3/4/5/6/7/8/9/10/')->getDepth());
    }

    public function test_is_ancestor_of_returns_false_for_self(): void
    {
        $path = MaterializedPath::fromString('1/2/');

        $this->assertFalse($path->isAncestorOf($path));
    }
}
