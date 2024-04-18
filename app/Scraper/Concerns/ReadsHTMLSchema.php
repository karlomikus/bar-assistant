<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Concerns;

use Symfony\Component\DomCrawler\Crawler;

trait ReadsHTMLSchema
{
    public function readHTML(): array
    {
        $html = [];
        $this->crawler->filterXPath('//*[@itemtype="http://schema.org/Recipe"]//*[@itemprop]')->each(function (Crawler $node) use (&$html) {
            $propName = $node->attr('itemprop');
            $elementName = $node->nodeName();

            if ($propName === 'description') {
                if ($elementName === 'meta') {
                    $html['description'] = $node->attr('content');
                } else {
                    $html['description'] = $node->text();
                }
            }

            if ($propName === 'name' && $elementName !== 'meta') {
                $html['name'] = $node->text();
            }

            if ($propName === 'recipeInstructions') {
                $html['recipeInstructions'][] = $node->text();
            }

            if ($propName === 'recipeIngredient') {
                $html['recipeIngredient'][] = $node->text();
            }

            if ($propName === 'ingredients') {
                $html['ingredients'][] = $node->text();
            }

            if ($propName === 'recipeCategory') {
                $html['recipeCategory'] = explode(',', $node->text());
            }

            if ($propName === 'keywords') {
                $html['keywords'] = explode(',', $node->attr('content'));
            }

            if ($propName === 'image') {
                if ($elementName === 'img') {
                    $html['image'] = $node->attr('src');
                } else {
                    $html['image'] = $node->text();
                }
            }

            $node->filterXPath('//*[@itemtype="http://schema.org/Person"]//*[@itemprop]')->each(function (Crawler $node) use (&$html) {
                $html['author'] = $node->attr('content');
            });
        });

        return $html;
    }
}