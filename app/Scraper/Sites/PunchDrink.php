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

        $result .= $this->parseEditorsNote();

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

        return [
            'uri' => $image['uri'],
            'copyright' => 'Punch Staff | ' . $photoAuthorEl->text(''),
        ];
    }

    private function parseEditorsNote(): string
    {
        $editorsNote = $this->crawler->filterXPath('//h5[contains(text(), "Editor\'s Note")]/following::p')->html('');
        if ($editorsNote === '') {
            return $editorsNote;
        }

        // Convert <br> to new lines
        $editorsNote = preg_replace('/<br\s?\/?>/ius', "\n\n", str_replace("\n", "", str_replace("\r", "", htmlspecialchars_decode($editorsNote))));
        // Convert bolds to markdown
        $editorsNote = preg_replace('/<(b|strong)>(.*?)<\/\1>/i', '### $2', $editorsNote);
        $editorsNote = str_replace('&nbsp;', '', $editorsNote);

        return "\n\n ## Editors note:\n\n" . htmlentities($editorsNote);
    }
}
