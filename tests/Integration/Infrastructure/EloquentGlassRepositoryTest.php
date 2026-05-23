<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Cocktail\Glass;
use BarAssistant\Domain\Cocktail\GlassId;
use Kami\Cocktail\Models\Glass as ModelGlass;
use Kami\Cocktail\Models\Image as ModelImage;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\RecordTimestamps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentGlassRepository;

final class EloquentGlassRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_and_finds_glass_with_images(): void
    {
        $membership = $this->setupBarMembership();
        $firstImage = ModelImage::factory()->create(['created_user_id' => $membership->user_id]);
        $secondImage = ModelImage::factory()->create(['created_user_id' => $membership->user_id]);

        $glass = Glass::create(
            barId: new BarId($membership->bar_id),
            name: Name::fromString('Coupe'),
            recordTimestamps: RecordTimestamps::createdNow(),
            description: 'Stemmed serving glass',
            volume: AmountWithUnits::from(180, Unit::from('ml')),
        );
        $glass->addImage(new ImageId($firstImage->id));
        $glass->addImage(new ImageId($secondImage->id));

        $repository = new EloquentGlassRepository();
        $savedGlass = $repository->save($glass);

        $this->assertNotNull($savedGlass->getId());
        $this->assertDatabaseHas('glasses', [
            'id' => $savedGlass->getId()?->value,
            'bar_id' => $membership->bar_id,
            'name' => 'Coupe',
            'description' => 'Stemmed serving glass',
            'volume' => 180,
            'volume_units' => 'ml',
        ]);
        $this->assertDatabaseHas('images', [
            'id' => $firstImage->id,
            'imageable_type' => ModelGlass::class,
            'imageable_id' => $savedGlass->getId()?->value,
        ]);
        $this->assertDatabaseHas('images', [
            'id' => $secondImage->id,
            'imageable_type' => ModelGlass::class,
            'imageable_id' => $savedGlass->getId()?->value,
        ]);

        $foundGlass = $repository->findById($savedGlass->getId() ?? new GlassId(0));

        $this->assertNotNull($foundGlass);
        $this->assertSame('Coupe', $foundGlass->getName()->toString());
        $this->assertSame('Stemmed serving glass', $foundGlass->getDescription());
        $this->assertSame(180.0, $foundGlass->getVolume()?->amountMin);
        $this->assertSame('ml', $foundGlass->getVolume()?->units->value);
        $this->assertCount(2, $foundGlass->getImages());
    }

    public function test_it_finds_all_glasses_in_bar(): void
    {
        $membership = $this->setupBarMembership();
        $otherMembership = $this->setupBarMembership();
        $firstGlass = ModelGlass::factory()->recycle($membership->bar)->create(['name' => 'Highball']);
        $secondGlass = ModelGlass::factory()->recycle($membership->bar)->create(['name' => 'Nick and Nora']);
        ModelGlass::factory()->recycle($otherMembership->bar)->create(['name' => 'Other bar']);

        $repository = new EloquentGlassRepository();
        $glasses = $repository->findAllInBar(new BarId($membership->bar_id));

        $this->assertCount(2, $glasses);
        $this->assertEqualsCanonicalizing(
            [$firstGlass->id, $secondGlass->id],
            array_map(static fn (Glass $glass): int => $glass->getId()?->value ?? 0, $glasses),
        );
    }

    public function test_it_deletes_glass(): void
    {
        $membership = $this->setupBarMembership();
        $glass = ModelGlass::factory()->recycle($membership->bar)->create();

        $repository = new EloquentGlassRepository();
        $repository->delete(new GlassId($glass->id));

        $this->assertDatabaseMissing('glasses', ['id' => $glass->id]);
    }
}
