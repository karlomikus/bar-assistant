<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Symfony\Component\Yaml\Yaml;
use Spatie\ArrayToXml\ArrayToXml;
use Kami\Cocktail\External\SupportsXML;
use Kami\Cocktail\External\SupportsYAML;
use Kami\Cocktail\External\SupportsDraft2;
use Kami\Cocktail\External\SupportsMarkdown;
use Kami\Cocktail\Models\Cocktail as CocktailModel;

readonly class Schema implements SupportsDraft2, SupportsXML, SupportsMarkdown, SupportsYAML
{
    public const SCHEMA_VERSION = 'draft2';
    public const SCHEMA_URL = 'https://barassistant.app/cocktail-02.schema.json';

    /**
     * @param array<IngredientBasic> $ingredients
     */
    public function __construct(
        public Cocktail $cocktail,
        public array $ingredients,
    ) {
    }

    public static function fromCocktailModel(CocktailModel $model): self
    {
        $ingredients = [];
        foreach ($model->ingredients as $cocktailIngredient) {
            $ingredients[] = IngredientBasic::fromModel($cocktailIngredient->ingredient);
            foreach ($cocktailIngredient->substitutes as $substitute) {
                $ingredients[] = IngredientBasic::fromModel($substitute->ingredient);
            }
        }

        return new self(
            Cocktail::fromModel($model),
            $ingredients,
        );
    }

    public static function fromDraft2Array(array $source): self
    {
        return new self(
            Cocktail::fromDraft2Array($source['recipe']),
            array_map(fn ($ingredient) => IngredientBasic::fromDraft2Array($ingredient), $source['ingredients']),
        );
    }

    public function toDraft2Array(): array
    {
        return [
            'recipe' => $this->cocktail->toDraft2Array(),
            'ingredients' => array_map(fn ($model) => $model->toDraft2Array(), $this->ingredients),
        ];
    }

    public function toXML(): string
    {
        return ArrayToXml::convert($this->toDraft2Array(), self::SCHEMA_VERSION, xmlEncoding: 'UTF-8');
    }

    public function toMarkdown(): string
    {
        $cocktail = $this->cocktail;

        return view('md_recipe_template', compact('cocktail'))->render();
    }

    public function toYAML(): string
    {
        return Yaml::dump($this->toDraft2Array(), 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }
}
