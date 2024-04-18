<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Concerns;

trait ReadsLDJson
{
    public function readJSON(): array
    {
        $nodes = $this->crawler->filterXPath('//script[@type="application/ld+json"]');
        $nodes = iterator_to_array($nodes);

        if (!$nodes) {
            return [];
        }

        $items = array_map(function($node) {
            $result = json_decode($node->textContent, true);
            if (isset($result['@graph'])) {
                $result = $result['@graph'];
            }

            if (!array_is_list($result)) {
                return [$result];
            }

            return $result;
        }, $nodes);

        $nodes = array_filter(array_merge(...$items));
        $nodes = array_values($nodes);

        $recipeSchema = [];
        foreach ($nodes as $node) {
            if (isset($node['@type']) && is_array($node['@type']) && in_array('Recipe', $node['@type'])) {
                $recipeSchema = $node;
                break;
            }

            if (isset($node['@type']) && $node['@type'] === 'Recipe') {
                $recipeSchema = $node;
                break;
            }
        }

        return $recipeSchema;
    }
}