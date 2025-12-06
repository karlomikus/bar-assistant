<?php

declare(strict_types=1);

namespace Kami\Cocktail\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Ingredient;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class IngredientListTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Retrieve a list of ingredients from the bar's inventory, optionally filtered by name.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $name = $request->get('name');
        $numberOfIngredients = $request->get('numberOfIngredients');
        $onlyInBarShelf = $request->get('onlyInBarShelf');

        $ingredients = Ingredient::where('ingredients.bar_id', bar()->id)->limit($numberOfIngredients)->orderBy('name', 'asc');

        if ($name) {
            $ingredients->where(function ($query) use ($name) {
                $query->orWhereRaw('LOWER(name) LIKE ?', ['%' . $name . '%'])
                    ->orWhereRaw('slug LIKE ?', ['%' . Str::slug($name) . '%']);
            });
        }

        if ($onlyInBarShelf === true) {
            $ingredients->join('bar_ingredients', 'bar_ingredients.ingredient_id', '=', 'ingredients.id');
        }

        $ingredients = $ingredients->get(['ingredients.id', 'ingredients.name']);

        return Response::text($ingredients->map(function ($ingredient) {
            return "- {$ingredient->name} (ID: {$ingredient->id})";
        })->implode("\n"));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Search ingredients by name.'),
            'onlyInBarShelf' => $schema->boolean()->description('If true, only ingredients currently in the bar shelf will be returned.')->default(false),
            'numberOfIngredients' => $schema->integer()->description('The number of ingredients to return.')->default(50),
        ];
    }
}
