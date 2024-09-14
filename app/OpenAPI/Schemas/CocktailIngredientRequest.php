<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['ingredient_id', 'amount', 'units'])]
readonly class CocktailIngredientRequest
{
    /**
     * @param CocktailIngredientSubstituteRequest[] $substitutes
     */
    public function __construct(
        #[OAT\Property(property: 'ingredient_id')]
        public int $id,
        #[OAT\Property()]
        public ?string $name,
        #[OAT\Property(example: 30)]
        public float $amount,
        #[OAT\Property(example: 'ml')]
        public string $units,
        #[OAT\Property()]
        public int $sort = 0,
        #[OAT\Property()]
        public bool $optional = false,
        #[OAT\Property(items: new OAT\Items(type: CocktailIngredientSubstituteRequest::class))]
        public array $substitutes = [],
        #[OAT\Property(property: 'amount_max', example: 60)]
        public ?float $amountMax = null,
        #[OAT\Property()]
        public ?string $note = null,
    ) {
    }

    /**
     * @param array<mixed> $source
     */
    public static function fromArray(array $source): self
    {
        $substitutes = [];
        foreach ($source['substitutes'] ?? [] as $sub) {
            $substitutes[] = CocktailIngredientSubstituteRequest::fromArray($sub);
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
