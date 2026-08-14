<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Symfony\Component\Yaml\Yaml;
use Spatie\ArrayToXml\ArrayToXml;
use Kami\Cocktail\External\SupportsXML;
use Kami\Cocktail\External\SupportsYAML;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\External\SupportsSchema4;
use Kami\Cocktail\External\SupportsMarkdown;
use Kami\Cocktail\Models\Cocktail as CocktailModel;

readonly class Schema implements SupportsSchema4, SupportsXML, SupportsMarkdown, SupportsYAML
{
    public const SCHEMA_VERSION = 'schema4';
    public const SCHEMA_URL = 'https://barassistant.app/cocktail-04.schema.json';

    public function __construct(
        public Cocktail $cocktail,
    ) {
    }

    public static function fromCocktailModel(CocktailModel $model, ?Units $toUnits = null): self
    {
        return new self(
            Cocktail::fromModel($model, toUnits: $toUnits),
        );
    }

    public static function fromSchema4Array(array $source): self
    {
        return new self(
            Cocktail::fromSchema4Array($source),
        );
    }

    public function toSchema4Array(): array
    {
        return $this->cocktail->toSchema4Array();
    }

    public function toXML(): string
    {
        return ArrayToXml::convert($this->toSchema4Array(), self::SCHEMA_VERSION, domProperties: ['preserveWhiteSpace' => true, 'formatOutput' => true], xmlEncoding: 'UTF-8');
    }

    public function toMarkdown(): string
    {
        $cocktail = $this->cocktail;

        return view('md_recipe_template', compact('cocktail'))->render();
    }

    public function toYAML(): string
    {
        return Yaml::dump($this->toSchema4Array(), 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }
}
