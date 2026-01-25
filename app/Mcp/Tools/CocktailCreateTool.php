<?php

declare(strict_types=1);

namespace Kami\Cocktail\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\JsonSchema\JsonSchema;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\OpenAPI\Schemas\CocktailRequest;
use Kami\Cocktail\External\Model\Schema as SchemaDraft2;

class CocktailCreateTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new cocktail recipe with full details including ingredients, preparation method, garnish, and metadata.

        This tool allows you to create complete cocktail recipes with:
        - Basic info (name, description, instructions, source, garnish)
        - Ingredients with amounts, units, and optional substitutes
        - Glass type, preparation method, and required utensils
        - Tags for categorization
        - Parent cocktail reference for variations

        All fields except 'name' and 'instructions' are optional, but adding ingredients is highly recommended to create useful recipes.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $cocktailDTO = CocktailRequest::fromMCPRequest($request);
        $service = app(CocktailService::class);
        $cocktail = $service->createCocktail($cocktailDTO);

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
            // Required fields
            'name' => $schema->string()->description('Cocktail name')->required(),
            'instructions' => $schema->string()->description('Step by step instructions on how to make the cocktail, supports markdown')->required(),

            // Basic optional fields
            'description' => $schema->string()->description('Brief description of the cocktail'),
            'source' => $schema->string()->description('Source of the recipe (URL or text reference)'),
            'garnish' => $schema->string()->description('Garnish description (e.g., "Lime wheel", "Orange peel")'),
            'year' => $schema->integer()->description('Year the cocktail was created or published'),

            // Related entities (use lookup tools to get valid IDs)
            'glass_id' => $schema->integer()->description('ID of the glass type (use GlassListTool to find valid IDs)'),
            'method_id' => $schema->integer()->description('ID of the preparation method (use MethodListTool to find valid IDs). Affects ABV calculation.'),
            'parent_cocktail_id' => $schema->integer()->description('ID of parent cocktail if this is a variation'),

            // Arrays
            'tags' => $schema->array()->description('Array of tag names for categorization. Tags will be auto-created if they don\'t exist.'),
            'images' => $schema->array()->description('Array of existing image IDs to attach to the cocktail'),
            'utensils' => $schema->array()->description('Array of utensil IDs required for making this cocktail (use UtensilListTool to find valid IDs)'),

            // Ingredients array (most important!)
            'ingredients' => $schema->array()->description(
                'Array of ingredient objects. Each ingredient must have: ' .
                'ingredient_id (int, required), amount (float, required), units (string, required). ' .
                'Optional fields: sort (int, display order), optional (bool), is_specified (bool, ignores descendants as substitutes), ' .
                'amount_max (float, for ranges like 30-45ml), note (string), ' .
                'substitutes (array of objects with ingredient_id, optional amount/amount_max/units)'
            ),
        ];
    }
}
