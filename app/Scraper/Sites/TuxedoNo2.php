<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\Cocktail\Utils;
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

        return $result;
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
            $isGarnish = $node->filter('.amount .unit')->count() === 0;

            if ($isGarnish) {
                return;
            }

            $parsedIngredientAmount = Utils::parseIngredientAmount($node->filter('.amount')->text());

            $result[] = [
                'amount' => $parsedIngredientAmount['amount'],
                'units' => $parsedIngredientAmount['units'],
                'name' => $node->filter('.ingredient a')->first()->text(),
                'optional' => false,
            ];
        });

        return $result;
    }

    public function garnish(): ?string
    {
        $garnish = null;

        $this->crawler->filter('.recipe__recipe ul')->first()->filter('li')->each(function ($node) use (&$garnish) {
            if ($node->filter('.amount .unit')->count() === 0) {
                $garnish .= $node->filter('.ingredient')->text() . "\n";
            }
        });

        return $garnish;
    }

    public function image(): ?array
    {
        $srcSet = $this->crawler->filter('.recipe__primary-image')->first()->attr('srcset');
        $sources = explode(' ', $srcSet);

        if (!$sources[2]) {
            return null;
        }

        return [
            'url' => $sources[2],
            'copyright' => 'TuxedoNo2',
        ];
    }
}
