<?php

use Kami\Cocktail\Http\Middleware\EnsureRequestHasBarQuery;
use Kami\Cocktail\Mcp\Servers\CocktailServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/cocktails', CocktailServer::class)->middleware(['auth:sanctum', EnsureRequestHasBarQuery::class]);;
