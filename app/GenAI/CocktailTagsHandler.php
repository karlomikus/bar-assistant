<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Kami\Cocktail\GenAI\DTO\CocktailTagsRequest;
use Kami\Cocktail\GenAI\DTO\PromptConfiguration;

final readonly class CocktailTagsHandler
{
    public function __construct(private CocktailTagsRequest $request)
    {
    }

    public function __invoke(): PromptConfiguration
    {
        $schema = new ObjectSchema(
            name: 'cocktail_tags',
            description: 'Cocktail tags',
            properties: [
                new ArraySchema(
                    'tags',
                    'Ordered list of 5-6 canonical cocktail tags for recommendations; first 3-4 must be flavor/sensory; lowercase; unique; prefer existing tags when semantically equivalent',
                    new StringSchema(
                        'tag',
                        'Single canonical cocktail tag in lowercase (1-2 words, 3 only if unavoidable); flavor-first vocabulary; avoid generic or redundant tags'
                    )
                ),
            ],
            requiredFields: ['tags']
        );

        $tags = implode(', ', $this->request->existingTags);
        $existingTagsContext = !empty($tags)
            ? "Here are the most commonly used tags in this bar: {$tags}"
            : "This bar has no existing tags yet.";

        $prompt = <<<PROMPT
            You are a cocktail flavor profiling assistant. Analyze the cocktail recipe and return tags optimized for recommendation quality.

            {$existingTagsContext}

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

            Cocktail recipe:
            {$this->request->cocktailRecipe}
        PROMPT;

        return new PromptConfiguration(trim($prompt), $schema);
    }
}
