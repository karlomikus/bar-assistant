<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Cocktail;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Cocktail\MethodId;
use BarAssistant\Domain\Cocktail\CocktailMethod;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\CocktailMethodRepository;
use Tests\Infrastructure\InMemoryCocktailMethodRepository;
use BarAssistant\Application\Cocktail\CocktailMethodService;
use BarAssistant\Application\Cocktail\DTO\CocktailMethodResult;
use BarAssistant\Application\Cocktail\DTO\CreateCocktailMethod;
use BarAssistant\Application\Cocktail\DTO\UpdateCocktailMethod;
use BarAssistant\Application\Exception\EntityNotFoundException;

final class CocktailMethodServiceTest extends TestCase
{
    private CocktailMethodRepository $cocktailMethodRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cocktailMethodRepository = new InMemoryCocktailMethodRepository([
            1 => CocktailMethod::create(
                barId: new BarId(10),
                name: Name::fromString('Shaking'),
                dilution: Dilution::fromFloat(25.0),
                recordTimestamps: RecordTimestamps::createdNow(),
                description: 'Shake with ice',
            )->setId(new MethodId(1)),
            2 => CocktailMethod::create(
                barId: new BarId(10),
                name: Name::fromString('Stirring'),
                dilution: Dilution::fromFloat(15.0),
                recordTimestamps: RecordTimestamps::createdNow(),
            )->setId(new MethodId(2)),
        ]);
    }

    public function test_creates_cocktail_method(): void
    {
        $service = new CocktailMethodService($this->cocktailMethodRepository);
        $request = new CreateCocktailMethod(
            barId: 10,
            name: 'Blending',
            dilutionPercentage: 30.0,
            description: 'Blend with crushed ice',
        );

        $result = $service->createCocktailMethod($request);

        $this->assertInstanceOf(CocktailMethodResult::class, $result);
        $this->assertGreaterThan(0, $result->id);
        $this->assertSame(10, $result->barId);
        $this->assertSame('Blending', $result->name);
        $this->assertSame(30.0, $result->dilutionPercentage);
        $this->assertSame('Blend with crushed ice', $result->description);
    }

    public function test_creates_cocktail_method_without_description(): void
    {
        $service = new CocktailMethodService($this->cocktailMethodRepository);
        $request = new CreateCocktailMethod(
            barId: 10,
            name: 'Muddling',
            dilutionPercentage: 5.0,
        );

        $result = $service->createCocktailMethod($request);

        $this->assertInstanceOf(CocktailMethodResult::class, $result);
        $this->assertGreaterThan(0, $result->id);
        $this->assertSame('Muddling', $result->name);
        $this->assertSame(5.0, $result->dilutionPercentage);
        $this->assertNull($result->description);
    }

    public function test_creates_multiple_cocktail_methods_with_distinct_ids(): void
    {
        $service = new CocktailMethodService($this->cocktailMethodRepository);

        $firstResult = $service->createCocktailMethod(new CreateCocktailMethod(
            barId: 10,
            name: 'Blending',
            dilutionPercentage: 30.0,
        ));

        $secondResult = $service->createCocktailMethod(new CreateCocktailMethod(
            barId: 10,
            name: 'Layering',
            dilutionPercentage: 0.0,
        ));

        $this->assertNotSame($firstResult->id, $secondResult->id);
    }

    public function test_updates_cocktail_method(): void
    {
        $service = new CocktailMethodService($this->cocktailMethodRepository);
        $request = new UpdateCocktailMethod(
            id: 1,
            name: 'Dry shaking',
            dilutionPercentage: 10.0,
            description: 'Shake without ice',
        );

        $result = $service->updateCocktailMethod($request);

        $this->assertInstanceOf(CocktailMethodResult::class, $result);
        $this->assertSame(1, $result->id);
        $this->assertSame(10, $result->barId);
        $this->assertSame('Dry shaking', $result->name);
        $this->assertSame(10.0, $result->dilutionPercentage);
        $this->assertSame('Shake without ice', $result->description);
    }

    public function test_updates_cocktail_method_clears_description(): void
    {
        $service = new CocktailMethodService($this->cocktailMethodRepository);
        $request = new UpdateCocktailMethod(
            id: 1,
            name: 'Shaking',
            dilutionPercentage: 25.0,
        );

        $result = $service->updateCocktailMethod($request);

        $this->assertSame(1, $result->id);
        $this->assertNull($result->description);
    }

    public function test_cannot_update_non_existing_cocktail_method(): void
    {
        $service = new CocktailMethodService($this->cocktailMethodRepository);
        $request = new UpdateCocktailMethod(
            id: 999,
            name: 'Unknown',
            dilutionPercentage: 0.0,
        );

        $this->expectException(EntityNotFoundException::class);
        $service->updateCocktailMethod($request);
    }
}
