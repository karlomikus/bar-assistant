<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use DomainException;
use DateTimeImmutable;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Cocktail\CocktailId;

final class Rating implements Identity
{
    private ?RatingId $id = null;

    private function __construct(
        private CocktailId $cocktailId,
        private MemberId $memberId,
        private int $value,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        if ($value < 1 || $value > 5) {
            throw new DomainException('Rating value must be between 1 and 5');
        }
    }

    public static function create(
        CocktailId $cocktailId,
        MemberId $memberId,
        int $value,
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            cocktailId: $cocktailId,
            memberId: $memberId,
            value: $value,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function createFromDatabase(
        RatingId $id,
        CocktailId $cocktailId,
        MemberId $memberId,
        int $value,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        $rating = new self(
            cocktailId: $cocktailId,
            memberId: $memberId,
            value: $value,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        $rating->id = $id;

        return $rating;
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?RatingId
    {
        return $this->id;
    }

    public function setId(RatingId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing rating');
        }

        $this->id = $id;

        return $this;
    }

    public function getCocktailId(): CocktailId
    {
        return $this->cocktailId;
    }

    public function getMemberId(): MemberId
    {
        return $this->memberId;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateValue(int $value): self
    {
        if ($value < 1 || $value > 5) {
            throw new DomainException('Rating value must be between 1 and 5');
        }

        $this->value = $value;
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }
}
