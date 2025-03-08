<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class ProfileSettings
{
    #[OAT\Property(property: 'language')]
    public ?string $language = null;
    #[OAT\Property(property: 'theme')]
    public ?string $theme = null;

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'language' => $this->language,
            'theme' => $this->theme,
        ];
    }
}
