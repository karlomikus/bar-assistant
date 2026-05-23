<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Color;
use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\DomainEventDispatcher;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Common\Traits\HasImages;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Ingredient\Event\IngredientStrengthChanged;

/**
 * Ingredient entity
 */
final class Ingredient implements Identity
{
    use HasImages;

    private ?IngredientId $id = null;

    /** @var ComplexIngredientPart[] */
    private array $ingredientParts = [];

    /** @var IngredientPrice[] */
    private array $prices = [];

    private function __construct(
        private readonly BarId $barId,
        private Name $name,
        private Authors $authors,
        private RecordTimestamps $recordTimestamps,
        private MaterializedPath $materializedPath,
        private ABV $strength,
        private ?string $description = null,
        private ?string $origin = null,
        private ?Color $color = null,
        private ?CalculatorId $calculatorId = null,
        private ?float $sugarContent = null,
        private ?float $acidity = null,
        private ?string $distillery = null,
        private ?Unit $units = null,
        private ?IngredientId $parentIngredientId = null,
    ) {
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

    public static function create(
        BarId $barId,
        Name $name,
        Authors $authors,
        RecordTimestamps $recordTimestamps,
        ?string $description = null,
        ?ABV $strength = null,
        ?string $origin = null,
        ?Color $color = null,
        ?CalculatorId $calculatorId = null,
        ?float $sugarContent = null,
        ?float $acidity = null,
        ?string $distillery = null,
        ?Unit $units = null,
        ?IngredientId $parentIngredientId = null,
        ?MaterializedPath $materializedPath = null,
    ): self {
        $materializedPath = $materializedPath ?? MaterializedPath::root();

        self::guardHierarchyState(
            parentIngredientId: $parentIngredientId,
            materializedPath: $materializedPath,
        );

        $ingredient = new self(
            barId: $barId,
            name: $name,
            authors: $authors,
            recordTimestamps: $recordTimestamps,
            description: $description,
            strength: $strength ?? ABV::from(0.0),
            origin: $origin,
            color: $color,
            calculatorId: $calculatorId,
            sugarContent: $sugarContent,
            acidity: $acidity,
            distillery: $distillery,
            units: $units,
            parentIngredientId: $parentIngredientId,
            materializedPath: $materializedPath,
        );

        return $ingredient;
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
    public function getName(): Name
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
    public function getStrength(): ABV
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
     * @return ComplexIngredientPart[]
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
     * @param ComplexIngredientPart $part Part ingredient with amount to add
     */
    public function addIngredientPart(ComplexIngredientPart $part): self
    {
        $ingredientId = $part->getIngredientId();

        foreach ($this->ingredientParts as $existingPart) {
            if ($existingPart->getIngredientId()->equals($ingredientId)) {
                return $this;
            }
        }

        $this->ingredientParts[] = $part;

        return $this;
    }

    public function removeIngredientPart(IngredientId $ingredientId): self
    {
        $this->ingredientParts = array_values(array_filter(
            $this->ingredientParts,
            static fn (ComplexIngredientPart $part) => !$part->getIngredientId()->equals($ingredientId)
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
     * @return IngredientPrice[]
     */
    public function getPrices(): array
    {
        return $this->prices;
    }

    public function addPrice(PriceCategoryId $priceCategoryId, float $price, string $currency, float $amount, string $units, ?string $description = null): self
    {
        $this->prices[] = IngredientPrice::create(
            priceCategoryId: $priceCategoryId,
            price: Price::createFromFloat($price, $currency),
            amountWithUnits: AmountWithUnits::from($amount, Unit::from($units)),
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
        Name $name,
        UserId $updatedBy,
        ?string $description = null,
        ?ABV $strength = null,
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

        if ($this->strength !== $strength) {
            DomainEventDispatcher::instance()->publish(new IngredientStrengthChanged(
                barId: $this->getBarId(),
                ingredientId: $this->getId(),
                oldStrength: $this->strength->toFloat(),
                newStrength: $strength?->toFloat() ?? 0.0,
            ));
        }

        $this->name = $name;
        $this->description = $description;
        $this->strength = $strength ?? ABV::from(0.0);
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

    private static function guardHierarchyState(?IngredientId $parentIngredientId, MaterializedPath $materializedPath): void
    {
        if ($parentIngredientId === null) {
            if (!$materializedPath->isRoot()) {
                throw new DomainException('Root ingredient cannot have a materialized path');
            }

            return;
        }

        if ($materializedPath->isRoot()) {
            throw new DomainException('Child ingredient must have a materialized path');
        }

        $pathParentId = $materializedPath->getParentId();
        if ($pathParentId === null || !$pathParentId->equals($parentIngredientId)) {
            throw new DomainException('Parent ingredient ID must match materialized path');
        }
    }
}
