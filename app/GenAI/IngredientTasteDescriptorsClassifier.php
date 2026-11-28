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
     * Fixed instructions for descriptors where the default "The ingredient tastes {name}." phrasing is ungrammatical.
     */
    private const array QUESTION_PHRASING_OVERRIDES = [
        'balanced' => 'The ingredient has a well-balanced flavour profile.',
        'bubblegum' => 'The ingredient tastes like bubblegum.',
        'caramel' => 'The ingredient tastes like caramel.',
        'cereal' => 'The ingredient tastes like cereal.',
        'clean finish' => 'The ingredient has a clean finish.',
        'clove' => 'The ingredient tastes like clove.',
        'coffee' => 'The ingredient tastes like coffee.',
        'complex' => 'The ingredient has a complex flavour profile.',
        'full body' => 'The ingredient has a full body.',
        'light body' => 'The ingredient has a light body.',
        'lingering finish' => 'The ingredient has a lingering finish.',
        'medium body' => 'The ingredient has a medium body.',
        'simple' => 'The ingredient has a simple flavour profile.',
        'vanilla' => 'The ingredient tastes like vanilla.',
        'warming' => 'The ingredient has a warming character.',
    ];

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

        $questions = $this->buildQuestions($descriptors);

        $timeout = config('bar-assistant.ai.timeout', 120);

        $response = Classification::of([
            'name' => $name,
            'category' => $category,
            'strength' => $strength,
            'description' => str($description ?? '')->limit(2000)->toString(),
        ])
            ->questions($questions)
            ->timeout(is_numeric($timeout) ? (int) $timeout : 120)
            ->classify();

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

    /**
     * Build boolean classification questions, one per descriptor.
     *
     * @param  array<string, string>  $descriptors  normalized name => display name
     * @return array<string, Boolean>
     */
    public function buildQuestions(array $descriptors): array
    {
        $questions = [];
        foreach ($descriptors as $normalized => $displayName) {
            $questions[$normalized] = new Boolean(
                instructions: self::QUESTION_PHRASING_OVERRIDES[$normalized]
                    ?? sprintf('The ingredient tastes %s.', strtolower($displayName)),
                criteria: [
                    'true' => 'The descriptor accurately describes a notable part of the ingredient\'s taste profile.',
                    'false' => 'The descriptor does not fit the ingredient.',
                ],
            );
        }

        return $questions;
    }
}
