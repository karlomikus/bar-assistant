<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Prism\Prism\Enums\Provider;

final readonly class GenAIProviderConfig
{
    /**
     * @param array<string, mixed> $providerOptions
     */
    public function __construct(
        public Provider $provider,
        public string $model,
        public int $timeout,
        private array $providerOptions = [],
    ) {
    }

    public static function fromConfig(): self
    {
        return self::fromPath('bar-assistant.ai');
    }

    public static function fromImageConfig(): self
    {
        return self::fromPath(
            'bar-assistant.ai.image',
            timeout: 60 * 5,
        );
    }

    /**
     * @param array<string, mixed> $providerOptions
     */
    private static function fromPath(string $configPath, array $providerOptions = [], ?int $timeout = null): self
    {
        $provider = Provider::tryFrom(config($configPath . '.provider'));
        if (empty($provider)) {
            throw new \Exception('AI provider not configured');
        }

        $model = config($configPath . '.model');
        if (empty($model)) {
            throw new \Exception('AI model not configured');
        }

        return new self(
            provider: $provider,
            model: $model,
            timeout: $timeout ?? (int) config($configPath . '.timeout'),
            providerOptions: $providerOptions,
        );
    }

    /**
     * @return array{timeout: int}
     */
    public function getClientOptions(): array
    {
        return ['timeout' => $this->timeout];
    }

    /**
     * @return array<string, mixed>
     */
    public function getProviderOptions(): array
    {
        return $this->providerOptions;
    }
}
