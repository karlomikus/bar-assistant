<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['providerId', 'code', 'codeVerifier'])]
class OAuthLoginRequest
{
    #[OAT\Property(example: 'google|facebook|keycloak')]
    public string $providerId;
    #[OAT\Property(example: '52f8b40c-7a71-4041-95fb-d115a82530cf.4206bcef-ca2a-4228-a5ca-aae12d5aac7b.d3edfb2b-5046-472f-aa12-857b78e6011d')]
    public string $code;
    #[OAT\Property(example: '479107e2ddb341a4a177bda6194ab6c9bea4a6e6be3440cbb930af9d66aec5bb14eff15738c7467c92324e2eab4278b0')]
    public string $codeVerifier;
}
