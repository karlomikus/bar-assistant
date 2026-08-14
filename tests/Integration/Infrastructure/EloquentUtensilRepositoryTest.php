<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Cocktail\Utensil;
use BarAssistant\Domain\Cocktail\UtensilId;
use BarAssistant\Domain\Common\RecordTimestamps;
use Kami\Cocktail\Models\Utensil as ModelUtensil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentUtensilRepository;

final class EloquentUtensilRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_and_finds_utensil(): void
    {
        $membership = $this->setupBarMembership();
        $utensil = Utensil::create(
            barId: new BarId($membership->bar_id),
            name: Name::fromString('Jigger'),
            recordTimestamps: RecordTimestamps::createdNow(),
            description: 'Double-sided measuring tool',
        );

        $repository = new EloquentUtensilRepository();
        $savedUtensil = $repository->save($utensil);

        $this->assertNotNull($savedUtensil->getId());
        $this->assertDatabaseHas('utensils', [
            'id' => $savedUtensil->getId()?->value,
            'bar_id' => $membership->bar_id,
            'name' => 'Jigger',
            'description' => 'Double-sided measuring tool',
        ]);

        $foundUtensil = $repository->findById($savedUtensil->getId() ?? new UtensilId(0));

        $this->assertNotNull($foundUtensil);
        $this->assertSame('Jigger', $foundUtensil->getName()->toString());
        $this->assertSame('Double-sided measuring tool', $foundUtensil->getDescription());
    }

    public function test_it_deletes_utensil(): void
    {
        $membership = $this->setupBarMembership();
        $utensil = ModelUtensil::factory()->recycle($membership->bar)->create();

        $repository = new EloquentUtensilRepository();
        $repository->delete(new UtensilId($utensil->id));

        $this->assertDatabaseMissing('utensils', ['id' => $utensil->id]);
    }
}
