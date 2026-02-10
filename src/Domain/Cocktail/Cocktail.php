<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use DomainException;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Common\RecordTimestamps;
use DateTimeImmutable;

final class Cocktail implements Identity
{
    private ?CocktailId $id = null;

    /**
     * @param CocktailIngredient[] $ingredients
     * @param string[] $tags
     * @param ImageId[] $images
     */
    private function __construct(
        private BarId $barId,
        private Name $name,
        private string $instructions,
        private Authors $authors,
        private RecordTimestamps $recordTimestamps,
        private PublicStatus $publicStatus,
        private ?GlassId $glassId = null,
        private ?MethodId $methodId = null,
        private ?string $description = null,
        private ?string $source = null,
        private ?string $garnish = null,
        private ?Dilution $dilution = null,
        private array $ingredients = [],
        private array $tags = [],
        private array $images = [],
        private ?CocktailId $variantOf = null,
        private ?int $year = null,
    ) {
    }

    /**
     * @param CocktailIngredient[] $ingredients
     */
    public static function create(
        BarId $barId,
        Name $name,
        string $instructions,
        Authors $authors,
        RecordTimestamps $recordTimestamps,
        PublicStatus $publicStatus,
        ?string $description = null,
        ?string $garnish = null,
        ?string $source = null,
        ?Dilution $dilution = null,
        array $ingredients = [],
        ?int $year = null,
        ?GlassId $glassId = null,
        ?MethodId $methodId = null,
        ?CocktailId $variantOf = null,
    ): self {
        return new self(
            barId: $barId,
            name: $name,
            instructions: $instructions,
            garnish: $garnish,
            dilution: $dilution,
            ingredients: $ingredients,
            authors: $authors,
            source: $source,
            recordTimestamps: $recordTimestamps,
            description: $description,
            year: $year,
            glassId: $glassId,
            methodId: $methodId,
            publicStatus: $publicStatus,
            variantOf: $variantOf,
        );
    }

    public function getId(): ?CocktailId
    {
        return $this->id;
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function setId(CocktailId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing cocktail');
        }

        $this->id = $id;

        return $this;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getInstructions(): string
    {
        return $this->instructions;
    }

    public function getGarnish(): ?string
    {
        return $this->garnish;
    }

    public function isVariant(): bool
    {
        return $this->variantOf !== null;
    }

    public function getABV(): ABV
    {
        if ($this->dilution === null) {
            return ABV::from(0.0);
        }

        $amountUsed = 0.0;
        foreach ($this->ingredients as $ingredient) {
            $amountUsed += $ingredient->amountWithUnits->amountMin;
        }

        $alcoholVolume = floatval(array_reduce(
            $this->ingredients,
            static fn ($carry, $item) => (($item->amountWithUnits->amountMin * $item->abv->toFloat()) / 100) + $carry,
        ));

        $afterDilution = ($amountUsed * $this->dilution->toDecimal()) + $amountUsed;

        if ($afterDilution <= 0) {
            return ABV::from(0.0);
        }

        return ABV::from(round(($alcoholVolume / $afterDilution) * 100, 2));
    }

    public function addIngredient(CocktailIngredient $ingredient): self
    {
        foreach ($this->ingredients as $existingIngredient) {
            if ($existingIngredient->ingredientId->equals($ingredient->ingredientId)) {
                return $this;
            }
        }

        $this->ingredients[] = $ingredient;

        return $this;
    }

    /**
     * @return CocktailIngredient[]
     */
    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getAuthors(): Authors
    {
        return $this->authors;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return ImageId[]
     */
    public function getImages(): array
    {
        return $this->images;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function getMethodId(): ?MethodId
    {
        return $this->methodId;
    }

    public function getGlassId(): ?GlassId
    {
        return $this->glassId;
    }

    public function isPublic(): bool
    {
        return $this->publicStatus->publicId !== null;
    }

    public function makePublic(?DateTimeImmutable $expiresAt = null): self
    {
        if ($this->isPublic()) {
            return $this;
        }

        $this->publicStatus = PublicStatus::createNowWithFutureExpirationDate($expiresAt);

        return $this;
    }

    public function getPublicStatus(): PublicStatus
    {
        return $this->publicStatus;
    }

    public function getVariantOf(): ?CocktailId
    {
        return $this->variantOf;
    }
}
