<?php

declare(strict_types=1);

namespace BarAssistant\Application\Review\DTO;

use BarAssistant\Domain\Review\Recommendation;

final readonly class CreateIngredientReviewRequest
{
    /**
     * @param string[] $tasteDescriptors
     */
    public function __construct(
        public int $barMembershipId,
        public int $ingredientId,
        public string $content,
        public ?Recommendation $recommendation = null,
        public array $tasteDescriptors = [],
    ) {
    }
}
