<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI;

use OpenApi\Attributes as OAT;

#[OAT\OpenApi(
    security: [['user_token' => []]],
)]
#[OAT\Info(
    title: "Bar Assistant API",
    version: "{{VERSION}}",
    description: "Bar Assistant is a self hosted application for managing your home bar.

    ## Content

    You should set `Content-Type: application/json` header for each request.

    ## Authentication

    Add your login token in header for every request, for example: `Authorization: Bearer 1|dvWHLWuZbmWWFbjaUDla393Q9jK5Ou9ujWYPcvII`.
    For requests that need reference to bar, add your bar id via query string, for example: `/api/example?bar_id=1`

    ## Authorization

    You will get response with error message and status code `403` if you try to access resource that you don't have permissions for.

    ## Sorting

    Some endpoints allow sorting by specific attributes. Prepending `-` defines descending order, and omitting it defines ascending order. Separate multiple sorts by a comma. For example: `?sort=name` will sort by name attribute in ascending order.

    ## Includes

    Some endpoints allow including extra relationship data on demand. Separate multiple relations witha a comma. For example: `?include=notes,user` will include extra extra data for notes and user.

    ## Pagination

    Some endpoints allow paginating results. Use `?per_page=30` to limit total results per request. Use `?page=3` to go to a specific page.

    ## Filtering

    Some endpoints allow filtering by a specific attribute. For example: `?filter[attribute_name]=value`.

    [Documentation](https://bar-assistant.github.io/docs/) | [Source](https://github.com/karlomikus/bar-assistant)",
)]
#[OAT\SecurityScheme(securityScheme: 'user_token', type: 'http', scheme: 'bearer')]
final class BarAssistantAPISchema
{
}
