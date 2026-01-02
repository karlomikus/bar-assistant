<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

final readonly class CreateIngredientDTO
{
    /**
     * @param int[] $images
     * @param int[] $complexIngredientParts
     * @param IngredientPriceRequest[] $prices
     */
    public function __construct(
        public int $barId,
        public string $name,
        public int $userId,
        public float $strength = 0.0,
        public ?string $description = null,
        public ?string $origin = null,
        public ?string $color = null,
        public ?int $parentIngredientId = null,
        public array $images = [],
        public array $complexIngredientParts = [],
        public array $prices = [],
        public ?int $calculatorId = null,
        public ?float $sugarContent = null,
        public ?float $acidity = null,
        public ?string $distillery = null,
        public ?string $units = null,
    ) {
    }

    public static function fromIlluminateRequest(\Illuminate\Http\Request $request, int $barId): self
    {
        $formPrices = $request->input('prices', []);

        $prices = [];
        if (is_array($formPrices)) {
            foreach ($formPrices as $price) {
                $prices[] = \Kami\Cocktail\OpenAPI\Schemas\IngredientPriceRequest::fromArray($price);
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
