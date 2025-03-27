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
    ) {
    }

    public static function fromLaminasEntry(EntryInterface $entry, string $source): self
    {
        $image = null;
        $imagePattern = '/<img[^>]+src="([^"]+)"/';
        preg_match($imagePattern, $entry->getContent(), $matches);
        if (isset($matches[1])) {
            $image = $matches[1];
        }

        /** @var string|null */
        $description = $entry->getDescription();

        return new self(
            source: e($source),
            title: e($entry->getTitle()),
            link: e($entry->getLink()),
            dateModified: $entry->getDateModified() ? DateTimeImmutable::createFromMutable($entry->getDateModified()) : null,
            description: e(html_entity_decode(strip_tags($description ?? ''), encoding: 'UTF-8')),
            image: $image,
        );
    }
}
