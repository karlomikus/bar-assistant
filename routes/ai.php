<?php

use Laravel\Mcp\Facades\Mcp;
use Kami\Cocktail\Mcp\Servers\CocktailServer;
use Kami\Cocktail\Http\Middleware\McpIsEnabled;
use Kami\Cocktail\Http\Middleware\EnsureRequestHasBarQuery;

Mcp::web('/mcp/cocktails', CocktailServer::class)->middleware(['auth:sanctum', McpIsEnabled::class, EnsureRequestHasBarQuery::class]);
