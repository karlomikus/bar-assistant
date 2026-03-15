<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Kami\Cocktail\GenAI\DTO\CocktailTagsRequest;
use Kami\Cocktail\GenAI\DTO\PromptConfiguration;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

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
                new ArraySchema('tags', 'List of recommended cocktail tags', new StringSchema('tag', 'A single cocktail tag')),
            ],
            requiredFields: ['tags']
        );

        $tags = implode(', ', $this->request->existingTags);
        $existingTagsContext = !empty($tags)
            ? "Here are the most commonly used tags in this bar: {$tags}"
            : "This bar has no existing tags yet.";

        $prompt = <<<PROMPT
            You are a cocktail expert assistant. Analyze the following cocktail recipe and suggest relevant tags.

            {$existingTagsContext}

            Instructions:
            - Generate 5-6 relevant tags (no more than 6)
            - Strongly prefer existing tags when they match the cocktail's characteristics
            - Only create new tags if existing ones don't adequately describe the cocktail
            - Tags should describe: flavor profile, occasion, difficulty, alcohol content, season, or ingredients
            - Keep tags concise (1-3 words each)
            - Use lowercase for consistency

            Cocktail recipe:
            {$this->request->cocktailRecipe}
        PROMPT;

        return new PromptConfiguration(trim($prompt), $schema);
    }
}
