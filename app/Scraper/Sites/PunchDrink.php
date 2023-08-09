<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

class PunchDrink extends DefaultScraper
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://punchdrink.com',
        ];
    }

    public function instructions(): ?string
    {
        $instructionsList = $this->crawler->filter('[itemprop="recipeInstructions"] li');
        if ($instructionsList->count() === 0) {
            return parent::instructions();
        }

        $i = 1;
        $result = "";
        $instructionsList->each(function ($node) use (&$result, &$i) {
            $result .= $i . ". " . trim($node->text()) . "\n";
            $i++;
        });

        return trim($result);
    }

    public function garnish(): ?string
    {
        $garnishEl = $this->crawler->filter('.garn-glass');
        if ($garnishEl->count() === 0) {
            return parent::garnish();
        }

        return $garnishEl->innerText();
    }

    public function image(): ?array
    {
        $image = parent::image();
        $photoAuthorEl = $this->crawler->filter('.photographer span');

        if ($photoAuthorEl->count() === 0) {
            return $image;
        }

        $image['copyright'] = 'Punch Staff | ' . $photoAuthorEl->text('');

        return $image;
    }
}
