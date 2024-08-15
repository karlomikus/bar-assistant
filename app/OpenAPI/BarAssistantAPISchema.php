<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI;

use OpenApi\Attributes as OAT;

#[OAT\OpenApi(
    security: [['user_token' => []]],
    servers: [
        new OAT\Server(url: 'http://localhost:8000/api', description: 'Local docker development'),
        new OAT\Server(url: 'https://api.barassistant.app/api', description: 'Production'),
    ],
)]
#[OAT\Info(
    license: new OAT\License(name: 'MIT', url: 'https://github.com/karlomikus/bar-assistant/blob/master/LICENSE'),
    contact: new OAT\Contact(name: 'Bar Assistant', url: 'https://barassistant.app', email: 'info@barassistant.app'),
    title: "Bar Assistant API",
    version: "{{VERSION}}",
    description: "**Bar Assistant** is all-in-one solution for managing your home bar. Compared to other recipe management software that usually tries to be more for general use, Bar Assistant is made specifically for managing cocktail recipes. This means that there are a lot of cocktail-oriented features, like ingredient substitutes, first-class ingredients, ABV calculations, unit switching and more.

[Homepage](https://barassistant.app/) &middot; [Official Documentation](https://bar-assistant.github.io/docs/) &middot; [GitHub Repository](https://github.com/karlomikus/bar-assistant)

## Rate Limiting

The rate limit is set to 1,000 requests per minute per IP address, or per user ID if authenticated. Certain endpoints have specific rate limits, such as importing and exporting data. Exporting is limited to 1 request per minute, while importing is restricted to 2 requests per minute for users without a subscription (applicable to cloud-hosted instances).

## Content-Type

Ensure that each request includes the `Content-Type: application/json` header.

## Auth

Include your login token in the header of every request, using the following format: `Authorization: Bearer 1|dvWHLWuZbmWWFbjaUDla393Q9jK5Ou9ujWYPcvII`.

## Bar context

For requests that require a reference to a specific bar, include the `bar_id` in the query string, e.g., `/cocktails?bar_id=1`, or use `Bar-Assistant-Bar-Id` header in your request.

## Authorization

A `403 Forbidden` status code will be returned if you attempt to access a resource without the necessary permissions.

## Sorting

Certain endpoints support sorting by specific attributes. Prepend `-` to an attribute for descending order, or omit it for ascending order. For example, `?sort=name` sorts by the `name` attribute in ascending order. Multiple sorts can be applied by separating attributes with a comma.

## Includes

Some endpoints support the inclusion of related data on demand. To include multiple relationships, separate them with a comma. For example, `?include=notes,user` will include additional data for both notes and the user.

## Pagination

To paginate results, use the `?per_page=30` parameter to limit the number of results per request. To navigate to a specific page, use `?page=3`.

## Filtering

Certain endpoints allow filtering by specific attributes. For example, `?filter[attribute_name]=value` filters results based on the given attribute. Multiple filter values can be separated by commas, e.g., `?filter[attribute_name]=value1,value2`."
)]
#[OAT\SecurityScheme(securityScheme: 'user_token', type: 'http', scheme: 'bearer')]
final class BarAssistantAPISchema
{
}
