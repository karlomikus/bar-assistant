<?php

declare(strict_types=1);

namespace Kami\Cocktail\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Kami\Cocktail\Models\Ingredient;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class IngredientDetailsTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Retrieve detailed information about a specific ingredient by its ID or slug.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $idOrSlug = $request->get('idOrSlug');

        $ingredient = Ingredient::where('bar_id', bar()->id)
            ->where(function ($query) use ($idOrSlug) {
                $query->where('slug', $idOrSlug)->orWhere('id', $idOrSlug);
            })
            ->first();

        if (!$ingredient) {
            return Response::error('Ingredient not found.');
        }

        if ($request->user()->cannot('show', $ingredient)) {
            return Response::error('Permission denied.');
        }

        $result = view('md_ingredient_template', compact('ingredient'))->render();

        return Response::text($result);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'idOrSlug' => $schema->string()->description('The ID or slug of the ingredient to retrieve details for.')->required(),
        ];
    }
}
