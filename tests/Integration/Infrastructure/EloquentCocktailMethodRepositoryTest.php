<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use DateTimeImmutable;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Cocktail\MethodId;
use BarAssistant\Domain\Cocktail\CocktailMethod;
use BarAssistant\Domain\Common\RecordTimestamps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Models\CocktailMethod as ModelCocktailMethod;
use Kami\Cocktail\Infrastructure\EloquentCocktailMethodRepository;

final class EloquentCocktailMethodRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_null_for_non_existent_method(): void
    {
        $repository = new EloquentCocktailMethodRepository();

        $method = $repository->findById(new MethodId(9999));

        $this->assertNull($method);
    }

    public function test_it_saves_and_finds_cocktail_method(): void
    {
        $membership = $this->setupBarMembership();
        $repository = new EloquentCocktailMethodRepository();

        $method = CocktailMethod::create(
            barId: new BarId($membership->bar_id),
            name: Name::fromString('Whip shake'),
            dilution: Dilution::fromFloat(18.0),
            recordTimestamps: RecordTimestamps::createdAt(new DateTimeImmutable('2025-01-01 12:00:00')),
            description: 'Short shake with a few ice cubes',
        );

        $savedMethod = $repository->save($method);

        $this->assertNotNull($savedMethod->getId());
        $this->assertSame($membership->bar_id, $savedMethod->getBarId()->value);
        $this->assertSame('Whip shake', $savedMethod->getName()->toString());
        $this->assertSame(18.0, $savedMethod->getDilution()->toFloat());
        $this->assertSame('Short shake with a few ice cubes', $savedMethod->getDescription());
        $this->assertSame('2025-01-01 12:00:00', $savedMethod->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s'));

        $this->assertDatabaseHas('cocktail_methods', [
            'id' => $savedMethod->getId()?->value,
            'bar_id' => $membership->bar_id,
            'name' => 'Whip shake',
            'dilution_percentage' => 18,
            'description' => 'Short shake with a few ice cubes',
            'created_at' => '2025-01-01 12:00:00',
        ]);

        $foundMethod = $repository->findById($savedMethod->getId());

        $this->assertNotNull($foundMethod);
        $this->assertSame($savedMethod->getId()?->value, $foundMethod->getId()?->value);
        $this->assertSame('Whip shake', $foundMethod->getName()->toString());
        $this->assertSame(18.0, $foundMethod->getDilution()->toFloat());
    }

    public function test_it_updates_existing_cocktail_method(): void
    {
        $membership = $this->setupBarMembership();
        $model = ModelCocktailMethod::factory()->for($membership->bar)->create([
            'name' => 'Shake',
            'dilution_percentage' => 25,
            'description' => 'Original description',
        ]);
        $repository = new EloquentCocktailMethodRepository();

        $method = $repository->findById(new MethodId($model->id));

        $this->assertNotNull($method);

        $method->updateDetails(
            name: Name::fromString('Roll'),
            dilution: Dilution::fromFloat(12.0),
            description: 'Gently roll between tins',
        );

        $savedMethod = $repository->save($method);

        $this->assertSame($model->id, $savedMethod->getId()?->value);
        $this->assertSame('Roll', $savedMethod->getName()->toString());
        $this->assertSame(12.0, $savedMethod->getDilution()->toFloat());
        $this->assertSame('Gently roll between tins', $savedMethod->getDescription());
        $this->assertNotNull($savedMethod->getRecordTimestamps()->getUpdatedAt());

        $this->assertDatabaseHas('cocktail_methods', [
            'id' => $model->id,
            'name' => 'Roll',
            'dilution_percentage' => 12,
            'description' => 'Gently roll between tins',
        ]);
    }

    public function test_it_finds_all_methods_in_a_bar(): void
    {
        $membership = $this->setupBarMembership();
        $otherMembership = $this->setupBarMembership();
        $firstMethod = ModelCocktailMethod::factory()->for($membership->bar)->create(['name' => 'Stir', 'dilution_percentage' => 20]);
        $secondMethod = ModelCocktailMethod::factory()->for($membership->bar)->create(['name' => 'Blend', 'dilution_percentage' => 30]);
        ModelCocktailMethod::factory()->for($otherMembership->bar)->create(['name' => 'Foreign', 'dilution_percentage' => 10]);
        $repository = new EloquentCocktailMethodRepository();

        $methods = $repository->findAllInBar(new BarId($membership->bar_id));

        $this->assertCount(2, $methods);
        $this->assertEqualsCanonicalizing(
            [$firstMethod->id, $secondMethod->id],
            array_map(static fn (CocktailMethod $method): int => $method->getId()?->value ?? 0, $methods),
        );
        $this->assertEqualsCanonicalizing(
            ['Stir', 'Blend'],
            array_map(static fn (CocktailMethod $method): string => $method->getName()->toString(), $methods),
        );
    }

    public function test_it_deletes_cocktail_method(): void
    {
        $membership = $this->setupBarMembership();
        $model = ModelCocktailMethod::factory()->for($membership->bar)->create();
        $repository = new EloquentCocktailMethodRepository();

        $repository->delete(new MethodId($model->id));

        $this->assertDatabaseMissing('cocktail_methods', ['id' => $model->id]);
    }
}
