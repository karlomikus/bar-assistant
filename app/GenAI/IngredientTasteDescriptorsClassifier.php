<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Laravel\Ai\Classification;
use Laravel\Ai\Classification\Boolean;
use Laravel\Ai\Responses\Data\BooleanAnswer;

final class IngredientTasteDescriptorsClassifier
{
    private const float DEFAULT_THRESHOLD = 0.7;

    private const int MAX_DESCRIPTORS = 7;

    /**
     * Classify an ingredient against the known taste descriptors and return the most fitting ones.
     *
     * @param  array<string, string>  $descriptors  normalized name => display name
     * @return array<int, string> normalized descriptor names, most fitting first
     */
    public function classify(
        string $name,
        ?string $category,
        ?float $strength,
        ?string $description,
        array $descriptors,
        ?float $threshold = null,
        ?int $maxDescriptors = null,
    ): array {
        $threshold ??= self::DEFAULT_THRESHOLD;
        $maxDescriptors ??= self::MAX_DESCRIPTORS;

        $questions = [];
        foreach ($descriptors as $normalized => $displayName) {
            $questions[$normalized] = new Boolean(
                instructions: sprintf('The ingredient tastes %s.', strtolower($displayName)),
                criteria: [
                    'true' => 'The descriptor accurately describes a notable part of the ingredient\'s taste profile.',
                    'false' => 'The descriptor does not fit the ingredient.',
                ],
            );
        }

        $timeout = config('bar-assistant.ai.timeout', 120);

        $response = Classification::of([
            'name' => $name,
            'category' => $category,
            'strength' => $strength,
            'description' => str($description ?? '')->limit(2000)->toString(),
        ])
            ->questions($questions)
            ->timeout(is_numeric($timeout) ? (int) $timeout : 120)
            ->classify(provider: $this->resolveProvider(), model: null);

        return $response->collect()
            ->filter(fn (object $answer): bool => $answer instanceof BooleanAnswer)
            ->filter(fn (BooleanAnswer $answer): bool => $answer->isTrue($threshold))
            ->map(fn (BooleanAnswer $answer): float => round($answer->probability, 2))
            ->sortDesc()
            ->take($maxDescriptors)
            ->keys()
            ->values()
            ->all();
    }

    private function resolveProvider(): ?string
    {
        $provider = config('bar-assistant.ai.provider');
        if (is_string($provider) && $provider !== '') {
            return $provider;
        }

        $default = config('ai.default');

        return is_string($default) ? $default : null;
    }
}
