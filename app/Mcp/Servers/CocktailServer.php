<?php

namespace Kami\Cocktail\Mcp\Servers;

use Kami\Cocktail\Mcp\Tools\CocktailDetailsTool;
use Kami\Cocktail\Mcp\Tools\CocktailSearchTool;
use Laravel\Mcp\Server;

class CocktailServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Cocktail Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '0.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        Instructions describing how to use the server and its features.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        CocktailDetailsTool::class,
        CocktailSearchTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
