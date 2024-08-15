<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Parameters;

use OpenApi\Attributes as OAT;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class BarIdHeaderParameter extends OAT\Parameter
{
    public function __construct()
    {
        parent::__construct(name: 'Bar-Assistant-Bar-Id', in: 'header', required: false, description: 'Database id of a bar. Required if you are not using `bar_id` query string.', schema: new OAT\Schema(type: 'integer'));
    }
}
