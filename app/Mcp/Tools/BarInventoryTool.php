<?php

declare(strict_types=1);

namespace Kami\Cocktail\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\JsonSchema\JsonSchema;

class BarInventoryTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Retrieve a list of currently in stock ingredients from the bar's inventory.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $numberOfIngredients = $request->get('numberOfIngredients');

        $ingredients = Ingredient::where('ingredients.bar_id', bar()->id)
            ->join('bar_ingredients', 'bar_ingredients.ingredient_id', '=', 'ingredients.id')
            ->limit($numberOfIngredients)->orderBy('name', 'asc')
            ->get(['ingredients.id', 'ingredients.name']);

        return Response::text($ingredients->map(fn ($ingredient) => "- {$ingredient->name} (ID: {$ingredient->id})")->implode("\n"));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    #[\Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'numberOfIngredients' => $schema->integer()->description('The number of ingredients to return.')->default(50),
        ];
    }
}
