<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use Brick\Money\Currency;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Exception\DomainException;

/**
 * PriceCategory Entity
 *
 * Represents a category for ingredient pricing within a bar context.
 * Each category has its own currency and can be used to organize
 * different price sources (Amazon, Local shops, etc...).
 */
final class PriceCategory implements Identity
{
    private ?PriceCategoryId $id = null;

    /**
     * @param BarId $barId Bar identifier
     * @param Name $name Name of the price category
     * @param Currency $currency Currency of the price category
     * @param null|string $description Description of the price category
     */
    public function __construct(
        private readonly BarId $barId,
        private Name $name,
        private Currency $currency,
        private ?string $description = null,
    ) {
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

    public function getName(): Name
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

    /**
     * Update the price category details
     *
     * @param Name $name New name
     * @param null|string $description New description
     */
    public function updateDetails(Name $name, ?string $description = null): self
    {
        $this->name = $name;
        $this->description = $description;

        return $this;
    }

    /**
     * Change the currency for this price category
     *
     * @param Currency $currency New currency
     */
    public function changeCurrency(Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }
}
