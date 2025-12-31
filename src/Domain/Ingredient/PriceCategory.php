<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\AggregateRoot;
use BarAssistant\Domain\Bar\BarId;
use Brick\Money\Currency;
use DomainException;

final class PriceCategory implements AggregateRoot
{
    private ?PriceCategoryId $id = null;

    /**
     * @param BarId $barId Bar identifier
     * @param non-empty-string $name Name of the price category
     * @param Currency $currency Currency of the price category
     * @param null|string $description Description of the price category
     */
    public function __construct(
        private BarId $barId,
        private string $name,
        private Currency $currency,
        private ?string $description = null,
    ) {
        if (trim($name) === '') {
            throw new DomainException('Price category name cannot be empty.');
        }
    }

    public function getId(): ?PriceCategoryId
    {
        return $this->id;
    }

    public function setId(PriceCategoryId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing price category');
        }

        $this->id = $id;

        return $this;
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
