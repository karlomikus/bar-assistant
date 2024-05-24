<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Export;

use League\Csv\Writer;
use League\Csv\ColumnConsistency;
use Illuminate\Support\Collection;
use Kami\Cocktail\Models\Cocktail;
use Kami\RecipeUtils\UnitConverter\Units;

final class CocktailsToCSV
{
    public function __construct(private readonly ?Units $toUnits)
    {
    }

    /**
     * @param Collection<int, Cocktail> $cocktails
     */
    public function process(Collection $cocktails): string
    {
        $units = $this->toUnits;
        if (!$units) {
            $units = Units::Ml;
        }

        $header = ['id', 'name', 'instructions', 'ingredients', 'garnish'];

        $validator = new ColumnConsistency();
        $csv = Writer::createFromString();
        $csv->setEndOfLine("\r\n");
        $csv->addValidator($validator, 'column_consistency');
        $csv->setEscape('');

        $csv->insertOne($header);
        $csv->insertAll($cocktails->map(function (Cocktail $cocktail) use ($units) {
            return [
                $cocktail->slug,
                $cocktail->name,
                $cocktail->instructions,
                $cocktail->ingredients->map(fn ($i) => $i->getConvertedTo($units)->printIngredient())->implode("\n"),
                $cocktail->garnish,
            ];
        }));

        return $csv->toString();
    }
}
