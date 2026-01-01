<?php

declare(strict_types=1);

namespace Kami\Cocktail\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Kami\Cocktail\Models\Cocktail;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Kami\Cocktail\External\Model\Schema as SchemaDraft2;

#[IsReadOnly]
class CocktailDetailsTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Retrieves detailed information about a cocktail recipe. It requires the slug or ID of the cocktail as input and returns the cocktail details in a structured format.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $idOrSlug = $request->get('idOrSlug');

        $cocktail = Cocktail::where('bar_id', bar()->id)
            ->where(function ($query) use ($idOrSlug) {
                $query->where('slug', $idOrSlug)->orWhere('id', $idOrSlug);
            })
            ->firstOrFail();

        if ($request->user()->cannot('show', $cocktail)) {
            return Response::error('Permission denied.');
        }

        $cocktail->loadDefaultRelations();

        $data = SchemaDraft2::fromCocktailModel($cocktail);

        return Response::text($data->toMarkdown());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'idOrSlug' => $schema->string()->description('The ID or slug of the cocktail to retrieve details for.')->required(),
        ];
    }
}
