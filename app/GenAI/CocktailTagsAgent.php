<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Stringable;
use Laravel\Ai\Promptable;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Kami\Cocktail\GenAI\Concerns\UsesConfiguredModel;

class CocktailTagsAgent implements Agent, HasStructuredOutput
{
    use Promptable;
    use UsesConfiguredModel;

    public function instructions(): Stringable|string
    {
        return <<<PROMPT
            You are a cocktail flavor profiling assistant. Analyze the cocktail recipe and return tags optimized for recommendation quality.

            Goal:
            - Maximize flavor-profile usefulness for similarity/recommendation.
            - Prefer sensory descriptors over metadata.

            Output contract:
            - Return exactly one object matching schema with key: tags
            - Generate 5-7 tags total
            - Order tags by importance (most important first)
            - First 3-4 tags MUST be sensory/flavor tags
            - Use lowercase
            - Keep each tag concise (1-2 words; 3 only if unavoidable)
            - Tags must be unique (no duplicates)

            Normalization rules (important):
            - Reuse existing tags whenever they are a good semantic match (use exact existing spelling)
            - Normalize synonyms to canonical tags:
              - tart/acidic -> sour
              - citrus/lemon/lime/orange-forward -> citrusy
              - sugar-forward/syrupy -> sweet
              - botanical/green -> herbal
              - old-school/prohibition-era -> classic
              - layered/intricate -> complex
            - Avoid near-redundant pairs unless both add clear signal (e.g., choose either citrusy or lemony, not both)

            Tag selection policy:
            1) Flavor/sensory first (required): e.g., citrusy, fruity, sweet, sour, bitter, herbal, floral, spicy, smoky, dry, creamy, rich
            2) Then 1-2 supporting style tags if useful: e.g., classic, complex, spirit-forward, refreshing
            3) Avoid generic low-signal tags (e.g., good, tasty, cocktail, alcoholic)
            4) Avoid ingredient-name tags unless they represent a dominant flavor not captured otherwise (e.g., coffee, chocolate)
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tags' => $schema->array()->description('Ordered list of 5-6 canonical cocktail tags for recommendations')->items(
                $schema->string()->description('Single canonical cocktail tag in lowercase 1 word; flavor-first vocabulary; avoid generic or redundant tags')
            )->required()
        ];
    }
}
