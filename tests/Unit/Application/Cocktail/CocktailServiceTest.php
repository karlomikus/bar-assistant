<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Cocktail;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\UtensilId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\PublicStatus;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\CocktailRepository;
use Tests\Infrastructure\InMemoryCocktailRepository;
use BarAssistant\Application\Cocktail\CocktailService;
use BarAssistant\Application\Cocktail\DTO\CopyCocktail;
use BarAssistant\Application\Cocktail\DTO\CocktailResult;
use BarAssistant\Application\Cocktail\DTO\CreateCocktail;
use BarAssistant\Application\Cocktail\DTO\UpdateCocktail;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Cocktail\DTO\ForceCocktailVisibility;
use BarAssistant\Application\Cocktail\DTO\ToggleCocktailVisibility;

final class CocktailServiceTest extends TestCase
{
    private CocktailRepository $cocktailRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $copySourceCocktail = $this->createStoredCocktail(id: 20, name: 'Negroni');
        $copySourceCocktail->addTag('Bitter');
        $copySourceCocktail->addTag('Classic');
        $copySourceCocktail->addUtensil(new UtensilId(30));
        $copySourceCocktail->addUtensil(new UtensilId(31));

        $updateTargetCocktail = $this->createStoredCocktail(id: 30, name: 'Old Fashioned');
        $updateTargetCocktail->addTag('Strong');
        $updateTargetCocktail->addTag('Spirit Forward');
        $updateTargetCocktail->addImage(new ImageId(401));
        $updateTargetCocktail->addImage(new ImageId(402));
        $updateTargetCocktail->addUtensil(new UtensilId(41));
        $updateTargetCocktail->addUtensil(new UtensilId(42));

        $this->cocktailRepository = new InMemoryCocktailRepository([
            10 => $this->createStoredCocktail(id: 10, name: 'Parent Cocktail'),
            20 => $copySourceCocktail,
            30 => $updateTargetCocktail,
            40 => $this->createStoredCocktail(id: 40, name: 'Visibility Cocktail'),
        ]);
    }

    public function test_creates_cocktail_with_tags_images_utensils_and_parent(): void
    {
        $service = new CocktailService($this->cocktailRepository);

        $result = $service->createCocktail(new CreateCocktail(
            barId: 10,
            name: 'Created Cocktail',
            instructions: 'Build over ice',
            userId: 50,
            dilution: 18.0,
            description: 'Created description',
            source: 'Created source',
            garnish: 'Orange peel',
            glassId: null,
            methodId: null,
            tags: ['Citrusy', 'Refreshing'],
            ingredients: [],
            images: [101, 102],
            utensils: [7, 8],
            parentCocktailId: 10,
            year: 2024,
        ));

        $this->assertInstanceOf(CocktailResult::class, $result);
        $this->assertGreaterThan(0, $result->id);
        $this->assertNotSame('', $result->slug);

        $createdCocktail = $this->cocktailRepository->findById(new CocktailId($result->id));

        $this->assertNotNull($createdCocktail);
        $this->assertSame('Created Cocktail', $createdCocktail->getName()->toString());
        $this->assertSame(['Citrusy', 'Refreshing'], $createdCocktail->getTags());
        $this->assertSame([101, 102], $this->extractImageIds($createdCocktail));
        $this->assertSame([7, 8], $this->extractUtensilIds($createdCocktail));
        $this->assertSame(10, $createdCocktail->getVariantOf()?->value);
    }

    public function test_updates_cocktail_replaces_tags_images_and_utensils(): void
    {
        $service = new CocktailService($this->cocktailRepository);

        $service->updateCocktail(new UpdateCocktail(
            cocktailId: 30,
            barId: 10,
            name: 'Updated Cocktail',
            instructions: 'Stir with ice and strain',
            userId: 55,
            dilution: 12.5,
            description: 'Updated description',
            source: 'Updated source',
            garnish: 'Lemon twist',
            glassId: null,
            methodId: null,
            tags: ['Bright', 'Short'],
            ingredients: [],
            images: [901],
            utensils: [61],
            parentCocktailId: 10,
            year: 1991,
        ));

        $updatedCocktail = $this->cocktailRepository->findById(new CocktailId(30));

        $this->assertNotNull($updatedCocktail);
        $this->assertSame('Updated Cocktail', $updatedCocktail->getName()->toString());
        $this->assertSame('Updated description', $updatedCocktail->getDescription());
        $this->assertSame('Updated source', $updatedCocktail->getSource());
        $this->assertSame('Lemon twist', $updatedCocktail->getGarnish());
        $this->assertSame(['Bright', 'Short'], $updatedCocktail->getTags());
        $this->assertSame([901], $this->extractImageIds($updatedCocktail));
        $this->assertSame([61], $this->extractUtensilIds($updatedCocktail));
        $this->assertSame(10, $updatedCocktail->getVariantOf()?->value);
        $this->assertSame(1991, $updatedCocktail->getYear());
        $this->assertSame(55, $updatedCocktail->getAuthors()->getUpdatedBy()?->value);
    }

    public function test_cannot_update_non_existing_cocktail(): void
    {
        $service = new CocktailService($this->cocktailRepository);

        $this->expectException(EntityNotFoundException::class);

        $service->updateCocktail(new UpdateCocktail(
            cocktailId: 999,
            barId: 10,
            name: 'Missing Cocktail',
            instructions: 'Build',
            userId: 1,
            dilution: 0.0,
            description: null,
            source: null,
            garnish: null,
            glassId: null,
            methodId: null,
            tags: [],
            ingredients: [],
            images: [],
            utensils: [],
            parentCocktailId: null,
            year: null,
        ));
    }

    public function test_toggle_visibility_makes_cocktail_public_and_private(): void
    {
        $service = new CocktailService($this->cocktailRepository);

        $service->toggleVisibility(new ToggleCocktailVisibility(
            cocktailId: 40,
            forceVisibility: ForceCocktailVisibility::Public,
        ));

        $cocktail = $this->cocktailRepository->findById(new CocktailId(40));

        $this->assertNotNull($cocktail);
        $this->assertTrue($cocktail->isPublic());

        $service->toggleVisibility(new ToggleCocktailVisibility(
            cocktailId: 40,
            forceVisibility: ForceCocktailVisibility::Private,
        ));

        $cocktail = $this->cocktailRepository->findById(new CocktailId(40));

        $this->assertNotNull($cocktail);
        $this->assertFalse($cocktail->isPublic());
    }

    public function test_copies_cocktail_with_tags_utensils_images_and_copy_author(): void
    {
        $service = new CocktailService($this->cocktailRepository);

        $result = $service->copyCocktail(new CopyCocktail(
            barId: 99,
            cocktailId: 20,
            userId: 77,
            images: [501, 502],
        ));

        $copiedCocktail = $this->cocktailRepository->findById(new CocktailId($result->id));

        $this->assertNotNull($copiedCocktail);
        $this->assertSame(99, $copiedCocktail->getBarId()->value);
        $this->assertSame('Negroni Copy', $copiedCocktail->getName()->toString());
        $this->assertSame(['Bitter', 'Classic'], $copiedCocktail->getTags());
        $this->assertSame([30, 31], $this->extractUtensilIds($copiedCocktail));
        $this->assertSame([501, 502], $this->extractImageIds($copiedCocktail));
        $this->assertSame(20, $copiedCocktail->getVariantOf()?->value);
        $this->assertSame(77, $copiedCocktail->getAuthors()->getCreatedBy()->value);
    }

    private function createStoredCocktail(int $id, string $name): Cocktail
    {
        return Cocktail::create(
            barId: new BarId(10),
            name: Name::fromString($name),
            instructions: 'Stir and strain',
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            publicStatus: PublicStatus::createPrivate(),
            description: 'Cocktail description',
            garnish: 'Orange peel',
            source: 'Cocktail source',
            dilution: Dilution::fromFloat(10.0),
            year: 2020,
        )->setId(new CocktailId($id));
    }

    /**
     * @return int[]
     */
    private function extractImageIds(Cocktail $cocktail): array
    {
        return array_map(static fn (ImageId $imageId): int => $imageId->value, $cocktail->getImages());
    }

    /**
     * @return int[]
     */
    private function extractUtensilIds(Cocktail $cocktail): array
    {
        return array_map(static fn (UtensilId $utensilId): int => $utensilId->value, $cocktail->getUtensils());
    }
}
