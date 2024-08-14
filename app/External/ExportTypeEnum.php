<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

use OpenApi\Attributes as OAT;

#[OAT\Schema(type: 'string')]
enum ExportTypeEnum: string
{
    case Datapack = 'datapack';
    case Schema = 'schema';
    case Markdown = 'md';
    case JSONLD = 'json+ld';
    case XML = 'xml';

    public function getFilenameContext(): string
    {
        return match ($this) {
            self::Datapack => 'datapack',
            self::Schema => 'schema',
            self::Markdown => 'markdown',
            self::JSONLD => 'jsonld',
            self::XML => 'xml',
        };
    }
}
