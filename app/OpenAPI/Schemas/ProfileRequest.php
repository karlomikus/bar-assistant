<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name', 'email'])]
class ProfileRequest
{
    public function __construct(
        #[OAT\Property(example: 'Bar Tender')]
        public string $name,
        #[OAT\Property(example: 'new@email.com')]
        public string $email,
        #[OAT\Property()]
        public ?ProfileSettings $settings = null,
        #[OAT\Property(property: 'bar_id')]
        public ?int $barId = null,
        #[OAT\Property(example: 'newpassword', format: 'password')]
        public ?string $password = null,
        #[OAT\Property(property: 'is_shelf_public')]
        public bool $isShelfPublic = false,
    ) {
    }

    public static function fromIlluminateRequest(Request $request): self
    {
        $inputSettings = $request->input('settings') ?? [];
        $validSettings = ['language', 'theme'];
        $settings = array_intersect_key($inputSettings, array_flip($validSettings));

        $settings = new ProfileSettings();
        $settings->language = $inputSettings['language'] ?? null;
        $settings->theme = $inputSettings['theme'] ?? null;

        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            settings: $settings,
            barId: $request->filled('bar_id') ? $request->integer('bar_id') : null,
            password: $request->input('password'),
            isShelfPublic: $request->boolean('is_shelf_public'),
        );
    }
}
