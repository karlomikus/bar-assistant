<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI;

use OpenApi\Attributes as OAT;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Attachable;
use OpenApi\Attributes\XmlContent;
use OpenApi\Attributes\JsonContent;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class SuccessfulResponse extends OAT\Response
{
    public function __construct(
        int|string|null $response = 200,
        ?string $description = 'Successful response',
        ?array $headers = [],
        MediaType|JsonContent|XmlContent|Attachable|array|null $content = null,
    ) {
        $headers = array_merge([
            new OAT\Header(header: 'x-ratelimit-limit', description: 'Max number of attempts.', schema: new OAT\Schema(type: 'integer')),
            new OAT\Header(header: 'x-ratelimit-remaining', description: 'Remaining number of attempts.', schema: new OAT\Schema(type: 'integer')),
        ], $headers);

        parent::__construct(response: $response, description: $description, headers: $headers, content: $content);
    }
}
