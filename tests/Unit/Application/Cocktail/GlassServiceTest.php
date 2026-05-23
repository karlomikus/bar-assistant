<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Cocktail;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Cocktail\Glass;
use BarAssistant\Domain\Cocktail\GlassId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\GlassRepository;
use Tests\Infrastructure\InMemoryGlassRepository;
use BarAssistant\Application\Cocktail\GlassService;
use BarAssistant\Application\Cocktail\DTO\CreateGlass;
use BarAssistant\Application\Cocktail\DTO\GlassResult;
use BarAssistant\Application\Cocktail\DTO\UpdateGlass;
use BarAssistant\Application\Exception\EntityNotFoundException;

final class GlassServiceTest extends TestCase
{
    private GlassRepository $glassRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->glassRepository = new InMemoryGlassRepository([
            1 => Glass::create(
                barId: new BarId(10),
                name: Name::fromString('Highball'),
                recordTimestamps: RecordTimestamps::createdNow(),
                description: 'A tall glass',
            )->setId(new GlassId(1)),
            2 => Glass::create(
                barId: new BarId(10),
                name: Name::fromString('Coupe'),
                recordTimestamps: RecordTimestamps::createdNow(),
            )->setId(new GlassId(2)),
        ]);
    }

    public function test_creates_glass(): void
    {
        $service = new GlassService($this->glassRepository);
        $request = new CreateGlass(
            barId: 10,
            name: 'Martini',
            description: 'A classic martini glass',
            volume: 180.0,
            units: 'ml',
        );

        $result = $service->createGlass($request);

        $this->assertInstanceOf(GlassResult::class, $result);
        $this->assertGreaterThan(0, $result->id);
        $this->assertSame(10, $result->barId);
        $this->assertSame('Martini', $result->name);
        $this->assertSame('A classic martini glass', $result->description);
        $this->assertSame(180.0, $result->volume);
        $this->assertSame('ml', $result->units);
    }

    public function test_creates_glass_without_optional_fields(): void
    {
        $service = new GlassService($this->glassRepository);
        $request = new CreateGlass(
            barId: 10,
            name: 'Rocks',
        );

        $result = $service->createGlass($request);

        $this->assertInstanceOf(GlassResult::class, $result);
        $this->assertGreaterThan(0, $result->id);
        $this->assertSame('Rocks', $result->name);
        $this->assertNull($result->description);
        $this->assertNull($result->volume);
        $this->assertNull($result->units);
    }

    public function test_creates_multiple_glasses_with_distinct_ids(): void
    {
        $service = new GlassService($this->glassRepository);

        $firstResult = $service->createGlass(new CreateGlass(
            barId: 10,
            name: 'Snifter',
        ));

        $secondResult = $service->createGlass(new CreateGlass(
            barId: 10,
            name: 'Tulip',
        ));

        $this->assertNotSame($firstResult->id, $secondResult->id);
    }

    public function test_creates_glass_with_images(): void
    {
        $service = new GlassService($this->glassRepository);
        $request = new CreateGlass(
            barId: 10,
            name: 'Flute',
            images: [101, 102],
        );

        $result = $service->createGlass($request);

        $this->assertSame([101, 102], $result->images);
    }

    public function test_updates_glass(): void
    {
        $service = new GlassService($this->glassRepository);
        $request = new UpdateGlass(
            glassId: 1,
            name: 'Highball Updated',
            description: 'An updated tall glass',
            volume: 350.0,
            units: 'ml',
        );

        $result = $service->updateGlass($request);

        $this->assertInstanceOf(GlassResult::class, $result);
        $this->assertSame(1, $result->id);
        $this->assertSame(10, $result->barId);
        $this->assertSame('Highball Updated', $result->name);
        $this->assertSame('An updated tall glass', $result->description);
        $this->assertSame(350.0, $result->volume);
        $this->assertSame('ml', $result->units);
    }

    public function test_updates_glass_clears_description(): void
    {
        $service = new GlassService($this->glassRepository);
        $request = new UpdateGlass(
            glassId: 1,
            name: 'Highball',
        );

        $result = $service->updateGlass($request);

        $this->assertSame(1, $result->id);
        $this->assertNull($result->description);
    }

    public function test_cannot_update_non_existing_glass(): void
    {
        $service = new GlassService($this->glassRepository);
        $request = new UpdateGlass(
            glassId: 999,
            name: 'Unknown',
        );

        $this->expectException(EntityNotFoundException::class);
        $service->updateGlass($request);
    }

    public function test_deletes_glass(): void
    {
        $service = new GlassService($this->glassRepository);

        $service->deleteGlass(1);

        $this->assertNull($this->glassRepository->findById(new GlassId(1)));
    }

    public function test_cannot_delete_non_existing_glass(): void
    {
        $service = new GlassService($this->glassRepository);

        $this->expectException(EntityNotFoundException::class);
        $service->deleteGlass(999);
    }
}
