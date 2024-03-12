<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Export;

use League\Csv\Writer;
use League\Csv\ColumnConsistency;
use Illuminate\Support\Collection;
use Kami\Cocktail\Models\Cocktail;

class CocktailsToCSV
{
    /**
     * @param Collection<int, Cocktail> $cocktails
     */
    public function process(Collection $cocktails): string
    {
        $header = ['id', 'name', 'instructions', 'ingredients', 'garnish'];

        $validator = new ColumnConsistency();
        $csv = Writer::createFromString();
        $csv->setEndOfLine("\r\n");
        $csv->addValidator($validator, 'column_consistency');
        $csv->setEscape('');

        $csv->insertOne($header);
        $csv->insertAll($cocktails->map(function (Cocktail $cocktail) {
            return [
                $cocktail->slug,
                $cocktail->name,
                $cocktail->instructions,
                $cocktail->ingredients->map(fn ($i) => $i->printIngredient())->implode("\n"),
                $cocktail->garnish,
            ];
        }));

        return $csv->toString();
    }
}
