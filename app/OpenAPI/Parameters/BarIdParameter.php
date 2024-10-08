<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Parameters;

use OpenApi\Attributes as OAT;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class BarIdParameter extends OAT\Parameter
{
    public function __construct()
    {
        parent::__construct(name: 'bar_id', in: 'query', required: false, description: 'Database id of a bar. Required if you are not using `Bar-Assistant-Bar-Id` header.', schema: new OAT\Schema(type: 'integer'));
    }
}
