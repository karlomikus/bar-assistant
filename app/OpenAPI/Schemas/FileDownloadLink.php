<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class FileDownloadLink
{
    #[OAT\Property(example: 'http://example.com/api/exports/29/download?t=2053f2e716f2dcddc0a2b767249235750e549af6d459cb1c65d4720e72404d88&e=1723480826', description: 'Absolute URL to download the export')]
    public string $url;
    #[OAT\Property(example: '2053f2e716f2dcddc0a2b767249235750e549af6d459cb1c65d4720e72404d88')]
    public string $token;
    #[OAT\Property(example: '2024-08-12T16:40:26+00:00')]
    public string $expires;
}
