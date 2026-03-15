<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Prism\Prism\Enums\Provider;

final readonly class GenAIProviderConfig
{
    public function __construct(
        public Provider $provider,
        public string $model,
        public int $timeout,
    )
    {
    }

    public static function fromConfig()
    {
        $provider = Provider::tryFrom(config('bar-assistant.ai.provider'));
        if (empty($provider)) {
            throw new \Exception('AI provider not configured');
        }

        $model = config('bar-assistant.ai.model');
        if (empty($model)) {
            throw new \Exception('AI model not configured');
        }

        return new self(
            provider: $provider,
            model: $model,
            timeout: (int) config('bar-assistant.ai.timeout'),
        );
    }

    public function getClientOptions(): array
    {
        return ['timeout' => $this->timeout];
    }
}
