<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Cocktail;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Cocktail\Utensil;
use BarAssistant\Domain\Cocktail\UtensilId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\UtensilRepository;
use Tests\Infrastructure\InMemoryUtensilRepository;
use BarAssistant\Application\Cocktail\UtensilService;
use BarAssistant\Application\Cocktail\DTO\CreateUtensil;
use BarAssistant\Application\Cocktail\DTO\UpdateUtensil;
use BarAssistant\Application\Cocktail\DTO\UtensilResult;
use BarAssistant\Application\Exception\EntityNotFoundException;

final class UtensilServiceTest extends TestCase
{
    private UtensilRepository $utensilRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->utensilRepository = new InMemoryUtensilRepository([
            10 => Utensil::create(
                barId: new BarId(10),
                name: Name::fromString('Boston shaker'),
                recordTimestamps: RecordTimestamps::createdNow(),
                description: 'A two-piece shaker',
            )->setId(new UtensilId(10)),
            20 => Utensil::create(
                barId: new BarId(10),
                name: Name::fromString('Bar spoon'),
                recordTimestamps: RecordTimestamps::createdNow(),
            )->setId(new UtensilId(20)),
        ]);
    }

    public function test_creates_utensil(): void
    {
        $service = new UtensilService($this->utensilRepository);

        $result = $service->createUtensil(new CreateUtensil(
            barId: 10,
            name: 'Jigger',
            description: 'A double-sided measuring tool',
        ));

        $this->assertInstanceOf(UtensilResult::class, $result);
        $this->assertGreaterThan(0, $result->id);
        $this->assertSame(10, $result->barId);
        $this->assertSame('Jigger', $result->name);
        $this->assertSame('A double-sided measuring tool', $result->description);

        $createdUtensil = $this->utensilRepository->findById(new UtensilId($result->id));

        $this->assertNotNull($createdUtensil);
        $this->assertSame('Jigger', $createdUtensil->getName()->toString());
        $this->assertSame('A double-sided measuring tool', $createdUtensil->getDescription());
    }

    public function test_updates_utensil(): void
    {
        $service = new UtensilService($this->utensilRepository);

        $result = $service->updateUtensil(new UpdateUtensil(
            utensilId: 10,
            name: 'Boston shaker updated',
            description: 'An updated shaker description',
        ));

        $this->assertInstanceOf(UtensilResult::class, $result);
        $this->assertSame(10, $result->id);
        $this->assertSame(10, $result->barId);
        $this->assertSame('Boston shaker updated', $result->name);
        $this->assertSame('An updated shaker description', $result->description);

        $updatedUtensil = $this->utensilRepository->findById(new UtensilId(10));

        $this->assertNotNull($updatedUtensil);
        $this->assertSame('Boston shaker updated', $updatedUtensil->getName()->toString());
        $this->assertSame('An updated shaker description', $updatedUtensil->getDescription());
    }

    public function test_updates_utensil_clears_description(): void
    {
        $service = new UtensilService($this->utensilRepository);

        $result = $service->updateUtensil(new UpdateUtensil(
            utensilId: 10,
            name: 'Boston shaker',
        ));

        $this->assertSame(10, $result->id);
        $this->assertNull($result->description);
        $this->assertNull($this->utensilRepository->findById(new UtensilId(10))?->getDescription());
    }

    public function test_cannot_update_non_existing_utensil(): void
    {
        $service = new UtensilService($this->utensilRepository);

        $this->expectException(EntityNotFoundException::class);

        $service->updateUtensil(new UpdateUtensil(
            utensilId: 999,
            name: 'Unknown utensil',
        ));
    }

    public function test_deletes_utensil(): void
    {
        $service = new UtensilService($this->utensilRepository);

        $service->deleteUtensil(10);

        $this->assertNull($this->utensilRepository->findById(new UtensilId(10)));
    }

    public function test_cannot_delete_non_existing_utensil(): void
    {
        $service = new UtensilService($this->utensilRepository);

        $this->expectException(EntityNotFoundException::class);

        $service->deleteUtensil(999);
    }
}
