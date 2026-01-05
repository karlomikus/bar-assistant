<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Support\AmountWithUnits;
use BarAssistant\Domain\Support\Authors;
use BarAssistant\Domain\Support\Color;
use BarAssistant\Domain\Support\Price;
use BarAssistant\Domain\Support\RecordTimestamps;
use BarAssistant\Domain\Support\Unit;
use BarAssistant\Domain\User\UserId;
use DateTimeImmutable;

/**
 * Ingredient entity
 */
final class Ingredient implements Identity
{
    private ?IngredientId $id = null;
    private Authors $authors;
    private RecordTimestamps $recordTimestamps;
    private MaterializedPath $materializedPath;

    /** @var IngredientId[] */
    private array $ingredientParts = [];

    /** @var ImageId[] */
    private array $images = [];

    /** @var IngredientPrice[] */
    private array $prices = [];

    /**
     * @param BarId $barId Bar identifier
     * @param string $name Name of the ingredient
     * @param UserId $createdBy Who created the ingredient
     * @param null|string $description Textual description of the ingredient
     * @param null|float $strength In alcohol percentage (0.0 - 100.0)
     * @param null|string $origin Geographical origin of the ingredient
     * @param null|Color $color Color of the ingredient
     * @param null|CalculatorId $calculatorId Associated calculator identifier
     * @param null|float $sugarContent Sugar content in g/ml
     * @param null|float $acidity Acidity value in percents
     * @param null|string $distillery Distillery name
     * @param null|Unit $units Default ingredient measurement units
     * @param null|IngredientId $parentIngredientId Parent ingredient identifier (for variants)
     * @param null|MaterializedPath $materializedPath Materialized path in the ingredient hierarchy
     * @param null|DateTimeImmutable $createdAt Creation timestamp
     * @return void
     * @throws DomainException 
     */
    public function __construct(
        private readonly BarId $barId,
        private string $name,
        UserId $createdBy,
        private ?string $description = null,
        private ?float $strength = null,
        private ?string $origin = null,
        private ?Color $color = null,
        private ?CalculatorId $calculatorId = null,
        private ?float $sugarContent = null,
        private ?float $acidity = null,
        private ?string $distillery = null,
        private ?Unit $units = null,
        private ?IngredientId $parentIngredientId = null,
        ?MaterializedPath $materializedPath = null,
        ?DateTimeImmutable $createdAt = null,
    ) {
        if (trim($name) === '') {
            throw new DomainException('Ingredient name cannot be empty');
        }

        if ($strength !== null && ($strength < 0.0 || $strength > 100.0)) {
            throw new DomainException('Ingredient strength must be between 0.0 and 100.0');
        }

        $this->name = $name;
        $this->description = $description;
        $this->strength = $strength;
        $this->origin = $origin;
        $this->color = $color;
        $this->calculatorId = $calculatorId;
        $this->sugarContent = $sugarContent;
        $this->acidity = $acidity;
        $this->distillery = $distillery;
        $this->units = $units;
        $this->materializedPath = $materializedPath ?? MaterializedPath::root();
        $this->authors = Authors::createdBy($createdBy);
        $this->recordTimestamps = $createdAt ? RecordTimestamps::createdAt($createdAt) : RecordTimestamps::createdNow();
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?IngredientId
    {
        return $this->id;
    }

    public function setId(IngredientId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing ingredient');
        }

        $this->id = $id;

        return $this;
    }

    public function setAsVariantOf(?self $parentIngredient): Ingredient
    {
        if ($parentIngredient !== null && !$parentIngredient->getBarId()->equals($this->getBarId())) {
            throw new DomainException('Parent ingredient must belong to the same bar');
        }

        $this->parentIngredientId = $parentIngredient?->getId();
        $this->materializedPath = $parentIngredient?->getId()
            ? $parentIngredient->getMaterializedPath()->append($parentIngredient->getId())
            : MaterializedPath::root();

        return $this;
    }

    /**
     * @internal Used only by IngredientHierarchyManager for bulk path updates.
     * Do not call directly - use IngredientHierarchyManager::changeParent() instead.
     */
    public function setMaterializedPath(MaterializedPath $path): Ingredient
    {
        $this->materializedPath = $path;

        return $this;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getParentIngredientId(): ?IngredientId
    {
        return $this->parentIngredientId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMaterializedPath(): MaterializedPath
    {
        return $this->materializedPath;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStrength(): ?float
    {
        return $this->strength;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function getColor(): ?Color
    {
        return $this->color;
    }

    /**
     * @return IngredientId[]
     */
    public function getIngredientParts(): array
    {
        return $this->ingredientParts;
    }

    public function isComplexIngredient(): bool
    {
        return !empty($this->ingredientParts);
    }

    public function addIngredientPart(Ingredient $partIngredient): self
    {
        if ($partIngredient->isTransient()) {
            throw new DomainException('Ingredient part must have an ID assigned');
        }

        if (!$this->getBarId()->equals($partIngredient->getBarId())) {
            throw new DomainException('All ingredient parts must belong to the same bar');
        }

        if (!$this->isTransient() && $this->getId()->equals($partIngredient->getId())) {
            throw new DomainException('Ingredient cannot contain itself as a part');
        }

        foreach ($this->ingredientParts as $existingPart) {
            if ($existingPart->equals($partIngredient->getId())) {
                return $this;
            }
        }

        $this->ingredientParts[] = $partIngredient->getId();

        return $this;
    }

    public function removeIngredientPart(IngredientId $ingredientId): self
    {
        $this->ingredientParts = array_values(array_filter(
            $this->ingredientParts,
            fn(IngredientId $part) => !$part->equals($ingredientId)
        ));

        return $this;
    }

    public function removeAllIngredientParts(): self
    {
        $this->ingredientParts = [];

        return $this;
    }

    public function isAncestorOf(self $other): bool
    {
        return $this->materializedPath->isAncestorOf($other->getMaterializedPath());
    }

    /**
     * @return ImageId[]
     */
    public function getImages(): array
    {
        return $this->images;
    }

    public function addImage(ImageId $imageId): self
    {
        foreach ($this->images as $existingImageId) {
            if ($existingImageId->equals($imageId)) {
                return $this;
            }
        }

        $this->images[] = $imageId;

        return $this;
    }

    public function removeImage(ImageId $imageId): self
    {
        $this->images = array_values(array_filter(
            $this->images,
            fn(ImageId $existingImageId) => !$existingImageId->equals($imageId)
        ));

        return $this;
    }

    public function removeAllImages(): self
    {
        $this->images = [];

        return $this;
    }

    /**
     * @return IngredientPrice[]
     */
    public function getPrices(): array
    {
        return $this->prices;
    }

    public function addPrice(PriceCategoryId $priceCategoryId, int $price, string $currency, float $amount, string $units, ?string $description = null): self
    {
        $this->prices[] = new IngredientPrice(
            priceCategoryId: $priceCategoryId,
            price: Price::createFromMinor($price, $currency),
            amountWithUnits: new AmountWithUnits($amount, new Unit($units)),
            description: $description,
        );

        return $this;
    }

    public function removeAllPrices(): self
    {
        $this->prices = [];

        return $this;
    }

    public function getAuthors(): Authors
    {
        return $this->authors;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function updateDetails(
        string $name,
        ?string $description = null,
        ?float $strength = null,
        ?string $origin = null,
        ?Color $color = null,
        ?CalculatorId $calculatorId = null,
        ?float $sugarContent = null,
        ?float $acidity = null,
        ?string $distillery = null,
        ?Unit $units = null,
    ): self {
        if (trim($name) === '') {
            throw new DomainException('Ingredient name cannot be empty');
        }

        if ($strength !== null && ($strength < 0.0 || $strength > 100.0)) {
            throw new DomainException('Ingredient strength must be between 0.0 and 100.0');
        }

        $this->name = $name;
        $this->description = $description;
        $this->strength = $strength;
        $this->origin = $origin;
        $this->color = $color;
        $this->calculatorId = $calculatorId;
        $this->sugarContent = $sugarContent;
        $this->acidity = $acidity;
        $this->distillery = $distillery;
        $this->units = $units;

        return $this;
    }

    public function wasUpdatedBy(UserId $userId, ?DateTimeImmutable $updatedAt = null): void
    {
        $this->authors = $this->authors->updatedBy($userId);
        $this->recordTimestamps = $updatedAt ? $this->recordTimestamps->updatedAt($updatedAt) : $this->recordTimestamps->updatedNow();
    }

    public function getCalculatorId(): ?CalculatorId
    {
        return $this->calculatorId;
    }

    public function getSugarContent(): ?float
    {
        return $this->sugarContent;
    }

    public function getAcidity(): ?float
    {
        return $this->acidity;
    }

    public function getDistillery(): ?string
    {
        return $this->distillery;
    }

    public function getUnits(): ?Unit
    {
        return $this->units;
    }
}
