<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Parameters;

use OpenApi\Attributes as OAT;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class PageParameter extends OAT\Parameter
{
    public function __construct()
    {
        parent::__construct(name: 'page', in: 'query', description: 'Set current page number', schema: new OAT\Schema(type: 'integer'));
    }
}
