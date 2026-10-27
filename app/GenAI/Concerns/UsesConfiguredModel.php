<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI\Concerns;

trait UsesConfiguredModel
{
    public function model(): ?string
    {
        $model = config('bar-assistant.ai.model');

        return is_string($model) && $model !== '' ? $model : null;
    }
}
