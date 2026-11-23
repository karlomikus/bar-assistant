<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class ProfileSettings
{
    #[OAT\Property(property: 'language', type: 'string', nullable: true, maxLength: 255)]
    public ?string $language = null;
    #[OAT\Property(property: 'theme', type: 'string', nullable: true, maxLength: 255)]
    public ?string $theme = null;

    /**
     * @return array{language: ?string, theme: ?string}
     */
    public function toArray(): array
    {
        return [
            'language' => $this->language,
            'theme' => $this->theme,
        ];
    }
}
