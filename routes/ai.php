<?php

use Kami\Cocktail\Mcp\Servers\CocktailServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/cocktails', CocktailServer::class);
