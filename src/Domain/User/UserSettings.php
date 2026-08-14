<?php

declare(strict_types=1);

namespace BarAssistant\Domain\User;

use JsonSerializable;

final readonly class UserSettings implements JsonSerializable
{
    public function __construct(
        public readonly ?string $language = null,
        public readonly ?string $theme = null,
    ) {
    }

    public static function default(): self
    {
        return new self(
            language: 'en',
            theme: null,
        );
    }

    /**
     * @param array{language?: ?string, theme?: ?string} $settings
     */
    public static function fromArray(array $settings): self
    {
        return new self(
            language: $settings['language'] ?? 'en',
            theme: $settings['theme'] ?? null,
        );
    }

    public function withLanguage(?string $language): self
    {
        return new self(
            language: $language,
            theme: $this->theme,
        );
    }

    public function withTheme(?string $theme): self
    {
        return new self(
            language: $this->language,
            theme: $theme,
        );
    }

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

    /**
     * @return array{language: ?string, theme: ?string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
