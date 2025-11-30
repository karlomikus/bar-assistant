<?php

declare(strict_types=1);

namespace Kami\Cocktail\Mcp\Tools;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Cocktail;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CocktailSearchTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Searches for cocktail recipes based on a given query. It returns a list of matching cocktails with their basic information.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $searchCocktailName = $request->get('name');
        $searchCocktailIngredients = $request->get('ingredients', []);
        $total = $request->get('numberOfCocktails');

        $cocktails = Cocktail::where('cocktails.bar_id', bar()->id)->limit($total);

        if ($searchCocktailName) {
            $cocktails->where(function ($query) use ($searchCocktailName) {
                $query->orWhereRaw('LOWER(name) LIKE ?', ['%' . $searchCocktailName . '%'])
                    ->orWhereRaw('slug LIKE ?', ['%' . Str::slug($searchCocktailName) . '%']);
            });
        }

        if (count($searchCocktailIngredients) > 0) {
            $cocktails->leftJoin('cocktail_ingredients', 'cocktails.id', '=', 'cocktail_ingredients.cocktail_id')
                ->leftJoin('ingredients', 'cocktail_ingredients.ingredient_id', '=', 'ingredients.id')->where(function (Builder $query) use ($searchCocktailIngredients) {
                    foreach ($searchCocktailIngredients as $ingredientName) {
                        $query->orWhereRaw('LOWER(ingredients.name) LIKE ?', ['%' . strtolower($ingredientName) . '%']);
                    }
                })->groupBy('cocktails.id')->havingRaw('COUNT(DISTINCT ingredients.id) = ?', [count($searchCocktailIngredients)]);
        }

        $cocktails = $cocktails->get(['cocktails.id', 'cocktails.name', 'cocktails.slug']);

        return Response::text($cocktails->map(function ($cocktail) {
            return "- {$cocktail->name} (ID: {$cocktail->id}, Slug: {$cocktail->slug})";
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
            'name' => $schema->string()->description('Search cocktails by name.'),
            'ingredients' => $schema->array()->description('Search cocktails by their ingredient names.'),
            'numberOfCocktails' => $schema->integer()->description('The number of cocktails to return.')->default(10),
        ];
    }
}
