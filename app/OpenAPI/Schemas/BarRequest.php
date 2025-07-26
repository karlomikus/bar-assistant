<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Kami\Cocktail\Models\Bar;
use OpenApi\Attributes as OAT;
use Symfony\Component\Uid\Ulid;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\External\BarOptionsEnum;

#[OAT\Schema(required: ['name'])]
class BarRequest
{
    #[OAT\Property(example: 'Bar name')]
    public string $name;
    #[OAT\Property(example: 'A short subtitle of a bar')]
    public ?string $subtitle = null;
    #[OAT\Property(example: 'Bar description')]
    public ?string $description = null;
    #[OAT\Property(example: 'bar-name-1')]
    public ?string $slug = null;
    #[OAT\Property(property: 'default_units', example: 'ml', type: 'string', enum: ['ml', 'cl', 'oz'], description: 'Used only as a setting for client apps.')]
    public ?string $defaultUnits = null;
    #[OAT\Property(property: 'default_currency', example: 'EUR', description: 'ISO 4217 format of currency. Used only as a setting for client apps.')]
    public ?string $defaultCurrency = null;
    #[OAT\Property(property: 'enable_invites', description: 'Enable users with invite code to join this bar. Default `false`.')]
    public bool $invitesEnabled = false;
    #[OAT\Property(description: 'List of data that the bar will start with. Cocktails cannot be imported without ingredients.')]
    public ?BarOptionsEnum $options;
    /** @var array<int> */
    #[OAT\Property(items: new OAT\Items(type: 'integer'), description: 'Existing image ids')]
    public array $images = [];
    #[OAT\Property(property: 'is_public', description: 'Allow public access to bar recipes. Default `false`.')]
    public bool $isPublic = false;

    public static function fromLaravelRequest(Request $request): self
    {
        $inviteEnabled = (bool) $request->post('enable_invites', '0');

        $result = new self();

        $result->options = BarOptionsEnum::tryFrom($request->input('options', ''));
        $result->name = $request->input('name');
        $result->subtitle = $request->input('subtitle');
        $result->description = $request->input('description');
        $result->invitesEnabled = $inviteEnabled;
        $result->isPublic = $request->boolean('is_public', false);
        if ($request->input('slug')) {
            $result->slug = Str::slug($request->input('slug'));
        }

        if ($defaultUnits = $request->input('default_units')) {
            $result->defaultUnits = Units::tryFrom($defaultUnits)?->value;
        }

        if ($defaultCurrency = $request->input('default_currency')) {
            $result->defaultCurrency = $defaultCurrency;
        }

        $result->images = array_map('intval', $request->input('images', []));

        return $result;
    }

    public function toLaravelModel(?Bar $model = null): Bar
    {
        $bar = $model ?? new Bar();

        $bar->name = $this->name;
        $bar->subtitle = $this->subtitle;
        $bar->description = $this->description;
        $bar->is_public = $this->isPublic;

        if ($this->invitesEnabled && $bar->invite_code === null) {
            $bar->invite_code = (string) new Ulid();
        } else {
            $bar->invite_code = null;
        }

        $bar->slug = $this->slug;
        if (!$bar->slug) {
            $bar->generateSlug();
        }

        $settings = $bar->settings ?? [];
        if ($this->defaultUnits) {
            $settings['default_units'] = $this->defaultUnits;
        }
        if ($this->defaultCurrency) {
            $settings['default_currency'] = $this->defaultCurrency;
        }
        $bar->settings = $settings;

        return $bar;
    }
}
