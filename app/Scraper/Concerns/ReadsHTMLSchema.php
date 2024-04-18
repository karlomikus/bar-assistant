<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Concerns;

use Kami\Cocktail\Scraper\SchemaModel;
use Symfony\Component\DomCrawler\Crawler;

trait ReadsHTMLSchema
{
    public function readHTML(Crawler $from): ?SchemaModel
    {
        $schemaModel = new SchemaModel();

        $from->each(function (Crawler $node) use ($schemaModel) {
            $propName = $node->attr('itemprop');
            $elementName = $node->nodeName();

            if ($propName === 'description') {
                if ($elementName === 'meta') {
                    $schemaModel->description = $node->attr('content');
                } else {
                    $schemaModel->description = $node->text();
                }
            }

            if ($propName === 'name' && $elementName !== 'meta') {
                $schemaModel->name = $node->text();
            }

            if ($propName === 'recipeInstructions') {
                $schemaModel->instructions[] = $node->text();
            }

            if ($propName === 'recipeIngredient') {
                $schemaModel->ingredients[] = $node->text();
            }

            if ($propName === 'ingredients') {
                $schemaModel->ingredients[] = $node->text();
            }

            if ($propName === 'recipeCategory') {
                $schemaModel->tags = explode(',', $node->text());
            }

            if ($propName === 'keywords' && count($schemaModel->tags) === 0) {
                $schemaModel->tags = explode(',', $node->attr('content'));
            }

            if ($propName === 'image') {
                if ($elementName === 'img') {
                    $schemaModel->image = $node->attr('src');
                } else {
                    $schemaModel->image = $node->text();
                }
            }

            $node->filterXPath('//*[@itemtype="http://schema.org/Person"]//*[@itemprop]')->each(function (Crawler $node) use ($schemaModel) {
                $schemaModel->author = $node->attr('content');
            });
        });

        // We'll just say scraping failed if no name is extracted...
        if (!$schemaModel->name) {
            return null;
        }

        return $schemaModel;
    }
}