<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

class SchemaModel
{
    public ?string $name = null;
    public ?string $description = null;
    /** @var array<string> */
    public array $instructions = [];
    /** @var array<string> */
    public array $tags = [];
    /** @var array<string> */
    public array $ingredients = [];
    public ?string $image = null;
    public ?string $author = null;
}
