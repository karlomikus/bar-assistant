<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\RecipeUtils\RecipeIngredient;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class TuxedoNo2 extends AbstractSiteExtractor
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://tuxedono2.com',
        ];
    }

    public function name(): string
    {
        return $this->crawler->filter('.recipe__header-title')->first()->text();
    }

    public function description(): ?string
    {
        return $this->crawler->filter('.recipe__header-subtitle')->first()->text();
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        $result = '';

        $step = 1;
        $this->crawler->filter('.recipe__recipe ol')->first()->filter('li')->each(function ($node) use (&$result, &$step) {
            $result .= trim($step . ". " . $node->text()) . "\n";
            $step++;
        });

        return trim($result);
    }

    public function tags(): array
    {
        $featuredIngredient = $this->crawler->filter('.site-container div.recipe__footer div.ingredient-card div.label.label--bottom-border.ingredient-card__label a')->last()->text();

        return $featuredIngredient ? [ucfirst($featuredIngredient)] : [];
    }

    public function glass(): ?string
    {
        $glassTag = $this->crawler->filter('.recipe__header-titles-and-icons .recipe__tag-icons a')->last()->attr('href');

        return str_replace(
            '-',
            ' ',
            str_replace('/tags/', '', $glassTag)
        );
    }

    public function ingredients(): array
    {
        $result = [];

        $this->crawler->filter('.recipe__recipe ul')->first()->filter('li')->each(function ($node) use (&$result) {
            $isGarnish = str_contains($node->filter('.ingredient')->text(), 'garnish');

            if ($isGarnish) {
                return;
            }

            $onlyUnits = 'part';
            if ($node->filter('.amount .unit')->count() > 0) {
                $onlyUnits = $node->filter('.amount .unit')->text();
            }

            $amountAndUnits = $node->filter('.amount')->text();
            $recipeIngredient = $this->ingredientParser->parseLine(str_replace($onlyUnits, '', $amountAndUnits) . ' ' . $onlyUnits, $this->defaultConvertTo, [Units::Dash]);

            if ($node->filter('.ingredient a')->count() === 0) {
                return;
            }

            $ingredientName = $this->hintCommonIngredients($node->filter('.ingredient a')->first()->text());

            $result[] = new RecipeIngredient(
                $ingredientName,
                $recipeIngredient->amount,
                $recipeIngredient->units,
                $recipeIngredient->source,
                $recipeIngredient->originalAmount,
                $recipeIngredient->comment,
                $recipeIngredient->amountMax
            );
        });

        return $result;
    }

    public function garnish(): ?string
    {
        $garnish = '';

        $this->crawler->filter('.recipe__recipe ul')->first()->filter('li')->each(function ($node) use (&$garnish) {
            if ($node->filter('.amount .unit')->count() === 0) {
                $garnish .= trim($node->filter('.ingredient')->text()) . "\n";
            }
        });

        return trim($garnish);
    }

    public function image(): ?array
    {
        $srcSet = $this->crawler->filter('.recipe__primary-image.recipe__primary-image--1')->first()->attr('srcset');
        $sources = explode(' ', $srcSet);

        if (!$sources[2]) {
            return null;
        }

        return [
            'uri' => $sources[2],
            'copyright' => 'TuxedoNo2',
        ];
    }

    public function method(): ?string
    {
        $method = $this->crawler->filter('.recipe__header-titles-and-icons .recipe__tag-icons a')->first()->attr('href');

        return str_replace(
            '-',
            ' ',
            str_replace('/tags/', '', $method)
        );
    }

    private function hintCommonIngredients(string $ingredientName): string
    {
        return match ($ingredientName) {
            'grenadine' => 'Grenadine syrup',
            'rye' => 'Rye whiskey',
            'bourbon' => 'Bourbon whiskey',
            'scotch' => 'Scotch whiskey',
            'angostura bitters', 'aromatic bitters' => 'Angostura aromatic bitters',
            'orange liqueur' => 'Triple Sec',
            'heavy cream' => 'Cream',
            'soda water' => 'Club soda',
            'coffee liqueur' => 'Kahlua coffee liqueur',
            'creme de cacao' => 'Dark Crème de Cacao',
            'fernet' => 'Fernet Branca',
            'benedictine' => 'Bénédictine',
            'herbsaint' => 'Absinthe',
            'blanco tequila' => 'Tequila',
            'peychaud\'s bitters' => 'Peychauds Bitters',
            default => $ingredientName
        };
    }
}
