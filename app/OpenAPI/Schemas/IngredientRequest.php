<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name'])]
class IngredientRequest
{
    public function __construct(
        public int $barId,
        #[OAT\Property(example: 'Gin')]
        public string $name,
        public int $userId,
        #[OAT\Property(example: 40.0)]
        public float $strength = 0.0,
        #[OAT\Property(example: 'Gin is a type of alcoholic spirit')]
        public ?string $description = null,
        #[OAT\Property(example: 'Worldwide')]
        public ?string $origin = null,
        #[OAT\Property(example: '#ffffff')]
        public ?string $color = null,
        #[OAT\Property(property: 'parent_ingredient_id', example: 1)]
        public ?int $parentIngredientId = null,
        /** @var int[] */
        #[OAT\Property(items: new OAT\Items(type: 'integer'), description: 'Existing image ids')]
        public array $images = [],
        /** @var int[] */
        #[OAT\Property(property: 'complex_ingredient_part_ids', items: new OAT\Items(type: 'integer'), description: 'Existing ingredient ids')]
        public array $complexIngredientParts = [],
        /** @var IngredientPriceRequest[] */
        #[OAT\Property(items: new OAT\Items(type: IngredientPriceRequest::class))]
        public array $prices = [],
        #[OAT\Property(property: 'calculator_id', example: 1, description: 'Calculator you want to attach to this ingredient')]
        public ?int $calculatorId = null,
        #[OAT\Property(property: 'sugar_g_per_ml', example: 0.24)]
        public ?float $sugarContent = null,
        #[OAT\Property(property: 'acidity', example: 0.10)]
        public ?float $acidity = null,
        #[OAT\Property(property: 'distillery', example: 'Buffalo trace')]
        public ?string $distillery = null,
        #[OAT\Property(property: 'units', example: 'ml', description: 'Default unit that would be used for this ingredient')]
        public ?string $units = null,
    ) {
    }

    public static function fromIlluminateRequest(Request $request, int $barId): self
    {
        $formPrices = $request->input('prices', []);

        $prices = [];
        if (is_array($formPrices)) {
            foreach ($formPrices as $price) {
                $prices[] = IngredientPriceRequest::fromArray($price);
            }
        }

        return new self(
            $barId,
            $request->input('name'),
            $request->user()->id,
            $request->float('strength'),
            $request->input('description'),
            $request->input('origin'),
            $request->input('color'),
            $request->filled('parent_ingredient_id') ? $request->integer('parent_ingredient_id') : null,
            $request->input('images', []),
            $request->input('complex_ingredient_part_ids', []),
            $prices,
            $request->filled('calculator_id') ? $request->integer('calculator_id') : null,
            $request->filled('sugar_g_per_ml') ? $request->float('sugar_g_per_ml') : null,
            $request->filled('acidity') ? $request->float('acidity') : null,
            $request->input('distillery'),
            $request->input('units'),
        );
    }
}
