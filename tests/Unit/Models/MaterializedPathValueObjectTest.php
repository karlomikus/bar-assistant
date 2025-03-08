<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\TestCase;
use Kami\Cocktail\Models\ValueObjects\MaterializedPath;
use Kami\Cocktail\Exceptions\IngredientPathTooDeepException;

class MaterializedPathValueObjectTest extends TestCase
{
    public function test_create_from_string(): void
    {
        $this->assertSame([1, 2, 3], MaterializedPath::fromString('1/2/3')->toArray());
        $this->assertSame([1], MaterializedPath::fromString('1/')->toArray());
        $this->assertSame([], MaterializedPath::fromString('')->toArray());
        $this->assertSame([], MaterializedPath::fromString(null)->toArray());
    }

    public function test_outputs_complete_path(): void
    {
        $this->assertSame('1/2/3/', MaterializedPath::fromString('1/2/3')->toStringPath());
        $this->assertSame('', MaterializedPath::fromString(null)->toStringPath());
        $this->assertSame('', MaterializedPath::fromString('')->toStringPath());
    }

    public function test_outputs_array(): void
    {
        $this->assertSame([1, 2, 3], MaterializedPath::fromString('1/2/3')->toArray());
        $this->assertSame([], MaterializedPath::fromString(null)->toArray());
        $this->assertSame([], MaterializedPath::fromString('')->toArray());
    }

    public function test_appends_to_path(): void
    {
        $testAppend = MaterializedPath::fromString('1/2/3');
        $newPath = $testAppend->append(10);

        $this->assertSame('1/2/3/10/', $newPath->toStringPath());

        $testAppend = MaterializedPath::fromString(null);
        $newPath = $testAppend->append(10);

        $this->assertSame('10/', $newPath->toStringPath());
    }

    public function test_appends_checks_max_depth(): void
    {
        $testAppend = MaterializedPath::fromString('1/2/3/4/5/6/7/8/9/10');

        $this->expectException(IngredientPathTooDeepException::class);
        $testAppend->append(11);
    }
}
