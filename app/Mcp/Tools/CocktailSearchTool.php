<?php

namespace Kami\Cocktail\Mcp\Tools;

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
        $searchQuery = $request->get('query');
        $total = $request->get('numberOfCocktails');

        $cocktails = Cocktail::where('bar_id', 557)
            ->where(function ($query) use ($searchQuery) {
                $query->orWhereRaw('LOWER(name) LIKE ?', ['%' . $searchQuery . '%'])
                    ->orWhereRaw('slug LIKE ?', ['%' . Str::slug($searchQuery) . '%']);
            })
            ->limit($total)
            ->get(['id', 'name', 'slug']);

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
            'query' => $schema->string()->description('The search query.')->required(),
            'numberOfCocktails' => $schema->integer()->description('The number of cocktails to return.')->default(10),
        ];
    }
}
