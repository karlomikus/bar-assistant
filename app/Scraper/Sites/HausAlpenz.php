<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\Cocktail\Scraper\AbstractSite;

class HausAlpenz extends AbstractSite
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://alpenz.com',
        ];
    }

    public function name(): string
    {
        return $this->crawler->filterXPath("//*[@class='recipeFull']/h1/text()")->text();
    }

    public function description(): ?string
    {
        return null;
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        $result = '';
        $step = 1;

        $this->crawler->filter(".recipeFull p:not(:first-child):not(.measure)")->each(function ($node) use (&$result, &$step) {
            if (!str_contains(strtolower($node->text()), 'garnish')) {
                $result .= $step . '. ' . trim($node->text()) . "\n";
            }
            $step++;
        });

        return trim($result);
    }

    public function tags(): array
    {
        return [];
    }

    public function glass(): ?string
    {
        $imageSource = $this->crawler->filter(".recipeFull h1 img:not(.protocol)")->attr('src');
        $urlSplit = explode('/', $imageSource);
        $svg = end($urlSplit);

        return match ($svg) {
            'glass-double-rocks.svg' => 'Cocktail',
            'glass-cocktail.svg' => 'Lowball',
            'glass-collins.svg' => 'Highball',
            'glass-flute.svg' => 'Champagne',
            'glass-snifter.svg' => 'Snifter glass',
            'glass-mug.svg' => 'Glass mug',
            'glass-goblet.svg' => 'Goblet',
            'glass-tiki.svg' => 'Tiki',
            'combo-pitcher.svg' => 'Pitcher',
            'glass-pint.svg' => 'Pint',
            'glass-pilsner.svg' => 'Pilsner',
            'combo-punchbowl.svg' => 'Punch bowl',
            default => null
        };
    }

    public function ingredients(): array
    {
        $result = [];
        $this->crawler->filter("p.measure")->each(function ($node) use (&$result) {
            $result[] = $this->ingredientParser->parseLine($node->text());
        });

        return $result;
    }

    public function garnish(): ?string
    {
        $result = null;

        $this->crawler->filter(".recipeFull p:not(:first-child):not(.measure)")->each(function ($node) use (&$result) {
            if (str_contains(strtolower($node->text()), 'garnish')) {
                $result = $node->text();
            }
        });

        return $result;
    }

    public function image(): ?array
    {
        $img = null;
        if ($this->crawler->filter(".recipePhoto img")->count() > 0) {
            $img = $this->crawler->filter(".recipePhoto img")->attr('src');
        } elseif ($this->crawler->filter(".recipePhotoNarrow img")->count() > 0) {
            $img = $this->crawler->filter(".recipePhotoNarrow img")->attr('src');
        }

        $copyrightAuthor = 'Haus Alpenz';
        if ($this->crawler->filter(".recipePhoto .photoattribution")->count() > 0) {
            $copyrightAuthor .= ' | ' . $this->crawler->filter(".recipePhoto .photoattribution")->text();
        }

        return [
            'uri' => $img,
            'copyright' => $copyrightAuthor,
        ];
    }
}
