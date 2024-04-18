<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Concerns;

use Kami\Cocktail\Scraper\SchemaModel;
use Symfony\Component\DomCrawler\Crawler;

trait ReadsLDJson
{
    public function readJSON(Crawler $fromNodes): ?SchemaModel
    {
        $nodes = iterator_to_array($fromNodes);

        if (!$nodes) {
            return [];
        }

        $items = array_map(function ($node) {
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

        if (count($recipeSchema) === 0) {
            return null;
        }

        $schemaModel = new SchemaModel();
        $schemaModel->name = $recipeSchema['name'] ?? null;
        $schemaModel->description = $recipeSchema['description'] ?? null;
        $schemaModel->instructions = $recipeSchema['recipeInstructions'] ?? [];

        if (isset($recipeSchema['recipeCategory'])) {
            $schemaModel->tags = is_array($recipeSchema['recipeCategory']) ? $recipeSchema['recipeCategory'] : [(string) $recipeSchema['recipeCategory']];
        }

        if (count($schemaModel->tags) === 0 && isset($recipeSchema['keywords'])) {
            $schemaModel->tags = is_array($recipeSchema['keywords']) ? $recipeSchema['keywords'] : [(string) $recipeSchema['keywords']];
        }

        if (isset($recipeSchema['recipeIngredient']) && is_array($recipeSchema['recipeIngredient'])) {
            $schemaModel->ingredients = $recipeSchema['recipeIngredient'];
        }

        if (isset($recipeSchema['ingredients']) && is_array($recipeSchema['ingredients'])) {
            $schemaModel->ingredients = $recipeSchema['ingredients'];
        }

        $images = $recipeSchema['image'] ?? null;

        if (is_array($images)) {
            $image = end($images);
        } elseif ($images === null) {
            $image = null;
        } else {
            $image = $images;
        }

        $schemaModel->image = $image;

        if (isset($recipeSchema['author']) && is_string($recipeSchema['author'])) {
            $schemaModel->author = $recipeSchema['author'];
        } else {
            $schemaModel->author = $recipeSchema['author']['name'] ?? null;
        }

        return $schemaModel;
    }
}
