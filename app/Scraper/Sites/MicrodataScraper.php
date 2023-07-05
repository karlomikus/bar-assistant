<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\Cocktail\Scraper\IngredientParser;
use Kami\Cocktail\Exceptions\ScrapeException;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class MicrodataScraper extends AbstractSiteExtractor
{
    public function __construct(string $url)
    {
        parent::__construct($url);

        if ($this->crawler->filter('[itemtype="http://schema.org/Recipe"]')->count() === 0) {
            throw new ScrapeException('Microdata schema not found on this site.');
        }
    }

    public static function getSupportedUrls(): array
    {
        return [];
    }

    public function name(): string
    {
        return $this->crawler->filter('[itemtype="http://schema.org/Recipe"] :not([itemscope]) [itemprop="name"]')->text();
    }

    public function description(): ?string
    {
        return $this->crawler->filterXPath("//*[@itemtype='http://schema.org/Recipe']/*[@itemprop='description']")->attr('content');
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        $result = '';

        $this->crawler->filter('[itemtype="http://schema.org/Recipe"] :not([itemscope]) [itemprop="recipeInstructions"]')->each(function ($node, $i) use (&$result) {
            $step = $node->text();

            $result .= ($i + 1) . ". " . $step . "\n\n";
        });

        return $result;
    }

    public function tags(): array
    {
        return [];
    }

    public function glass(): ?string
    {
        return null;
    }

    public function ingredients(): array
    {
        $result = [];

        $this->crawler->filter('[itemtype="http://schema.org/Recipe"] :not([itemscope]) [itemprop="recipeIngredient"]')->each(function ($node) use (&$result) {
            $ingredient = $node->text();

            ['amount' => $amount, 'units' => $units, 'name' => $name] = (new IngredientParser($ingredient))->parse();

            if (empty($amount) || empty($name) || empty($units)) {
                return;
            }

            $result[] = [
                'amount' => $amount,
                'units' => $units,
                'name' => $name,
                'optional' => false,
            ];
        });

        return $result;
    }

    public function image(): ?array
    {
        $image = $this->crawler->filter('[itemtype="http://schema.org/Recipe"] :not([itemscope]) [itemprop="image"]')->attr('src');
        $copyright = $this->crawler->filter('[itemtype="http://schema.org/Person"] [itemprop="name"]')->attr('content');

        return [
            'url' => $image,
            'copyright' => $copyright,
        ];
    }
}
