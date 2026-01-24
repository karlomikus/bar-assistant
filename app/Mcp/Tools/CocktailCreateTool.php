<?php

declare(strict_types=1);

namespace Kami\Cocktail\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\OpenAPI\Schemas\CocktailRequest;
use Kami\Cocktail\Services\CocktailService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Kami\Cocktail\External\Model\Schema as SchemaDraft2;

class CocktailCreateTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new cocktail based on give input data.
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
            'name' => $schema->string()->description('Cocktail name'),
            'instructions' => $schema->string()->description('How to make the cocktail, supports markdown'),
        ];
    }
}
