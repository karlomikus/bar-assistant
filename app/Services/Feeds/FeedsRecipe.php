<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Feeds;

use DateTimeImmutable;
use Laminas\Feed\Reader\Entry\EntryInterface;

final readonly class FeedsRecipe
{
    public function __construct(
        public string $source,
        public string $title,
        public string $link,
        public ?DateTimeImmutable $dateModified,
        public ?string $description = null,
        public ?string $image = null,
        public bool $supportsRecipeImport = true,
    ) {
    }

    public static function fromLaminasEntry(EntryInterface $entry, string $source, bool $supportsRecipeImport = true): self
    {
        $image = null;
        $imagePattern = '/<img[^>]+src="([^"]+)"/';
        preg_match($imagePattern, html_entity_decode($entry->getContent()), $matches);
        if (isset($matches[1])) {
            $image = $matches[1];
        }

        /** @var string|null */
        $description = $entry->getDescription();
        if (!$description) {
            /** @var string|null */
            $description = $entry->getContent();
        }

        return new self(
            source: e($source),
            title: html_entity_decode(e($entry->getTitle()), ENT_SUBSTITUTE | ENT_HTML5),
            link: e($entry->getLink()),
            dateModified: $entry->getDateModified() ? DateTimeImmutable::createFromMutable($entry->getDateModified()) : null,
            description: e(html_entity_decode(strip_tags($description ?? ''), encoding: 'UTF-8')),
            image: $image,
            supportsRecipeImport: $supportsRecipeImport,
        );
    }
}
