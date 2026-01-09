<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\DomainEventDispatcher;
use BarAssistant\Domain\Ingredient\Event\IngredientStrengthChanged;
use BarAssistant\Domain\Support\AmountWithUnits;
use BarAssistant\Domain\Support\Authors;
use BarAssistant\Domain\Support\Color;
use BarAssistant\Domain\Support\Price;
use BarAssistant\Domain\Support\RecordTimestamps;
use BarAssistant\Domain\Support\Unit;
use BarAssistant\Domain\User\UserId;

/**
 * Ingredient entity
 */
final class Ingredient implements Identity
{
    private ?IngredientId $id = null;
    private MaterializedPath $materializedPath;

    /** @var IngredientId[] */
    private array $ingredientParts = [];

    /** @var ImageId[] */
    private array $images = [];

    /** @var IngredientPrice[] */
    private array $prices = [];

    public function __construct(
        private readonly BarId $barId,
        private string $name,
        private Authors $authors,
        private RecordTimestamps $recordTimestamps,
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

    /**
     * Set this ingredient as a variant of another ingredient
     *
     * Updates materialized path and parent ingredient reference
     *
     * @param null|Ingredient $parentIngredient If null will set as a root ingredient
     * @throws DomainException
     */
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
     * Used by IngredientHierarchyManager for bulk path updates.
     * @internal
     */
    public function setMaterializedPath(MaterializedPath $path): Ingredient
    {
        $this->materializedPath = $path;

        return $this;
    }

    /**
     * Bar identifier
     */
    public function getBarId(): BarId
    {
        return $this->barId;
    }

    /**
     * Parent ingredient identifier (for variants), returns null for root ingredients
     */
    public function getParentIngredientId(): ?IngredientId
    {
        return $this->parentIngredientId;
    }

    /**
     * Name of the ingredient
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Materialized path in the ingredient hierarchy
     */
    public function getMaterializedPath(): MaterializedPath
    {
        return $this->materializedPath;
    }

    /**
     * Textual description of the ingredient
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * In alcohol percentage (0.0 - 100.0)
     */
    public function getStrength(): ?float
    {
        return $this->strength;
    }

    /**
     * Geographical origin of the ingredient
     */
    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    /**
     * Color of the ingredient
     */
    public function getColor(): ?Color
    {
        return $this->color;
    }

    /**
     * Return the ingredient parts (for complex ingredients)
     *
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

    /**
     * Add an ingredient part (for complex ingredients)
     *
     * @param Ingredient $partIngredient Part ingredient to add
     */
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

    /**
     * Who created or updated the ingredient
     */
    public function getAuthors(): Authors
    {
        return $this->authors;
    }

    /**
     * When was ingredient created or updated
     */
    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    /**
     * Update existing ingredient details
     */
    public function updateDetails(
        string $name,
        UserId $updatedBy,
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
        if ($this->isTransient()) {
            throw new DomainException('Cannot update details of a transient ingredient');
        }

        if (trim($name) === '') {
            throw new DomainException('Ingredient name cannot be empty');
        }

        if ($strength !== null && ($strength < 0.0 || $strength > 100.0)) {
            throw new DomainException('Ingredient strength must be between 0.0 and 100.0');
        }

        if ($this->strength !== $strength) {
            DomainEventDispatcher::instance()->publish(new IngredientStrengthChanged(
                barId: $this->getBarId(),
                ingredientId: $this->getId(),
                oldStrength: $this->strength,
                newStrength: $strength,
            ));
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
        $this->authors = $this->authors->updatedBy($updatedBy);
        $this->recordTimestamps = $this->recordTimestamps->updatedNow();

        return $this;
    }

    /**
     * Associated calculator identifier
     */
    public function getCalculatorId(): ?CalculatorId
    {
        return $this->calculatorId;
    }

    /**
     * Sugar content in g/ml
     */
    public function getSugarContent(): ?float
    {
        return $this->sugarContent;
    }

    /**
     * Acidity value in percents
     */
    public function getAcidity(): ?float
    {
        return $this->acidity;
    }

    /**
     * Distillery name
     */
    public function getDistillery(): ?string
    {
        return $this->distillery;
    }

    /**
     * Default ingredient measurement units
     */
    public function getUnits(): ?Unit
    {
        return $this->units;
    }
}
