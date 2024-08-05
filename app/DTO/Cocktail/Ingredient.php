<?php

declare(strict_types=1);

namespace Kami\Cocktail\DTO\Cocktail;

readonly class Ingredient
{
    /**
     * @param array<Substitute> $substitutes
     */
    public function __construct(
        public int $id,
        public ?string $name,
        public float $amount,
        public string $units,
        public int $sort = 0,
        public bool $optional = false,
        public array $substitutes = [],
        public ?float $amountMax = null,
        public ?string $note = null
    ) {
    }

    public static function fromArray(array $source): self
    {
        $substitutes = [];
        foreach ($source['substitutes'] ?? [] as $sub) {
            $substitutes[] = Substitute::fromArray($sub);
        }

        return new self(
            (int) $source['ingredient_id'],
            null,
            (float) $source['amount'],
            $source['units'],
            (int) $source['sort'],
            $source['optional'] ?? false,
            $substitutes,
            ($source['amount_max'] ?? null) !== null ? (float) $source['amount_max'] : null,
            $source['note'] ?? null
        );
    }
}
