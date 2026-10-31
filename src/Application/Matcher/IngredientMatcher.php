<?php

declare(strict_types=1);

namespace BarAssistant\Application\Matcher;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\User\UserId;
use Wamania\Snowball\StemmerFactory;
use Wamania\Snowball\Stemmer\Stemmer;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientMatch;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use BarAssistant\Domain\Ingredient\IngredientMatchRepository;
use BarAssistant\Application\Matcher\DTO\IngredientMatchRequest;

final class IngredientMatcher
{
    /** @var array<string, IngredientMatch> */
    private array $matchedIngredients = [];

    private readonly Stemmer $stemmer;

    public function __construct(
        private readonly IngredientRepository $ingredientRepository,
        private readonly IngredientMatchRepository $ingredientMatchRepository,
    ) {
        $this->stemmer = StemmerFactory::create('en');
    }

    /**
     * Matches an ingredient by name within the bar without creating a new one.
     *
     * @return int|null Ingredient ID or null when no ingredient matches the name
     */
    public function matchByName(IngredientMatchRequest $request): ?int
    {
        $matchName = mb_strtolower($request->ingredientName);

        if (isset($this->matchedIngredients[$matchName])) {
            return $this->matchedIngredients[$matchName]->getId()->value;
        }

        $existingIngredient = $this->loadBarIngredients(new BarId($request->barId))[$matchName] ?? null;
        if ($existingIngredient) {
            return $existingIngredient->getId()->value;
        }

        return null;
    }

    /**
     * Matches an ingredient by name within the bar without creating a new one. Falls back to a
     * LIKE-style containment match when no exact match exists: a bar ingredient matches when its
     * name contains every word of the given name, comparing raw and stemmed forms. The shortest
     * matching ingredient name wins, as the most generic archetype.
     *
     * @return int|null Ingredient ID or null when no ingredient matches the name
     */
    public function matchBySimilarName(IngredientMatchRequest $request): ?int
    {
        $exactMatch = $this->matchByName($request);
        if ($exactMatch !== null) {
            return $exactMatch;
        }

        $matchName = mb_strtolower($request->ingredientName);
        $searchWords = $this->stemWords(preg_split('/\s+/', $matchName, -1, PREG_SPLIT_NO_EMPTY) ?: []);
        if ($searchWords === []) {
            return null;
        }

        $candidates = [];
        foreach ($this->loadBarIngredients(new BarId($request->barId)) as $barIngredient) {
            $ingredientName = $barIngredient->getName()->toLowercase();
            $ingredientWords = $this->stemWords(preg_split('/\s+/', $ingredientName, -1, PREG_SPLIT_NO_EMPTY) ?: []);

            // A bar ingredient matches when one name contains every word of the other, so both
            // "Scotch whisky" <-> "Blended scotch whisky" style pairs match regardless of direction.
            if (!$this->allWordsContainedIn($searchWords, $ingredientName) && !$this->allWordsContainedIn($ingredientWords, $matchName)) {
                continue;
            }

            $candidates[] = $barIngredient;
        }

        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            fn (IngredientMatch $a, IngredientMatch $b): int => mb_strlen($a->getName()->toLowercase()) <=> mb_strlen($b->getName()->toLowercase()),
        );

        $bestMatch = $candidates[0];
        $this->matchedIngredients[$matchName] = $bestMatch;

        return $bestMatch->getId()->value;
    }

    /**
     * @return array<string, IngredientMatch>
     */
    private function loadBarIngredients(BarId $barId): array
    {
        foreach ($this->ingredientMatchRepository->findManyByBarId($barId) as $barIngredient) {
            $this->matchedIngredients[$barIngredient->getName()->toLowercase()] = $barIngredient;
        }

        return $this->matchedIngredients;
    }

    /**
     * @param list<array{string, string}> $needleWords Original word and stemmed word pairs
     */
    private function allWordsContainedIn(array $needleWords, string $haystack): bool
    {
        foreach ($needleWords as [$word, $stemmedWord]) {
            if (!str_contains($haystack, $stemmedWord) && !str_contains($haystack, $word)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<string> $words
     * @return list<array{string, string}> Original word and stemmed word pairs
     */
    private function stemWords(array $words): array
    {
        $pairs = [];
        foreach ($words as $word) {
            $pairs[] = [$word, $this->stemmer->stem($word)];
        }

        return $pairs;
    }

    /**
     * Matches an ingredient by name within the bar. If no match is found, a new ingredient is created.
     *
     * @return int Ingredient ID
     */
    public function matchOrCreateByName(IngredientMatchRequest $request): int
    {
        $matched = $this->matchByName($request);
        if ($matched !== null) {
            return $matched;
        }

        $matchName = mb_strtolower($request->ingredientName);
        if (isset($this->matchedIngredients[$matchName])) {
            return $this->matchedIngredients[$matchName]->getId()->value;
        }

        $newIngredient = Ingredient::create(
            barId: new BarId($request->barId),
            name: Name::fromString($request->ingredientName),
            authors: Authors::createdBy(new UserId($request->userId)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $newIngredient = $this->ingredientRepository->save($newIngredient);
        if ($newIngredient->isTransient()) {
            throw new \RuntimeException('Failed to create new ingredient');
        }

        return $newIngredient->getId()->value;
    }
}
