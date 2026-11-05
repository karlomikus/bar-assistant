<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Matcher;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\IngredientMatch;
use Tests\Infrastructure\InMemoryIngredientRepository;
use BarAssistant\Application\Matcher\IngredientMatcher;
use Tests\Infrastructure\InMemoryIngredientMatchRepository;
use BarAssistant\Application\Matcher\DTO\IngredientMatchRequest;

final class IngredientMatcherTest extends TestCase
{
    private InMemoryIngredientRepository $ingredientRepository;
    private InMemoryIngredientMatchRepository $matchRepository;

    public function setUp(): void
    {
        $this->ingredientRepository = new InMemoryIngredientRepository();
        $this->matchRepository = new InMemoryIngredientMatchRepository([
            IngredientMatch::create(new IngredientId(542), Name::fromString('Cointreau')),
            IngredientMatch::create(new IngredientId(543), Name::fromString('Gin')),
        ]);
    }

    private function makeMatcher(): IngredientMatcher
    {
        return new IngredientMatcher($this->ingredientRepository, $this->matchRepository);
    }

    private function makeRequest(string $name): IngredientMatchRequest
    {
        return new IngredientMatchRequest(barId: 65, userId: 45, ingredientName: $name);
    }

    private function makeMatcherWithNames(string ...$names): IngredientMatcher
    {
        $matches = [];
        $id = 600;
        foreach ($names as $name) {
            $matches[] = IngredientMatch::create(new IngredientId($id++), Name::fromString($name));
        }

        return new IngredientMatcher($this->ingredientRepository, new InMemoryIngredientMatchRepository($matches));
    }

    public function testMatchByNameReturnsIdForExistingIngredient(): void
    {
        $matcher = $this->makeMatcher();

        $this->assertSame(542, $matcher->matchByName($this->makeRequest('Cointreau')));
    }

    public function testMatchByNameIsCaseInsensitive(): void
    {
        $matcher = $this->makeMatcher();

        $this->assertSame(543, $matcher->matchByName($this->makeRequest('GIN')));
        $this->assertSame(542, $matcher->matchByName($this->makeRequest('cointreau')));
    }

    public function testMatchByNameReturnsNullForUnknownName(): void
    {
        $matcher = $this->makeMatcher();

        $this->assertNull($matcher->matchByName($this->makeRequest('Captin Morgon')));
    }

    public function testMatchByNameNeverCreatesIngredient(): void
    {
        $matcher = $this->makeMatcher();

        $matcher->matchByName($this->makeRequest('Captin Morgon'));

        $this->assertSame([], $this->ingredientRepository->list(new BarId(65)));
    }

    public function testMatchBySimilarNameMatchesWhenBarIngredientNameContainsAllWords(): void
    {
        $matcher = $this->makeMatcherWithNames('Blended scotch whisky');

        $this->assertSame(600, $matcher->matchBySimilarName($this->makeRequest('Scotch whisky')));
    }

    public function testMatchBySimilarNameMatchesWhenGivenNameContainsAllWords(): void
    {
        $matcher = $this->makeMatcherWithNames('Scotch whisky');

        $this->assertSame(600, $matcher->matchBySimilarName($this->makeRequest('Blended scotch whisky')));
    }

    public function testMatchBySimilarNameComparesRawAndStemmedWords(): void
    {
        // "blended" stems to "blend", which is contained in "blended"
        $matcher = $this->makeMatcherWithNames('Blended scotch whisky');

        $this->assertSame(600, $matcher->matchBySimilarName($this->makeRequest('blend scotch whisky')));
    }

    public function testMatchBySimilarNamePrefersShortestCandidate(): void
    {
        $matcher = $this->makeMatcherWithNames('Sloe gin', 'London dry gin');

        $this->assertSame(600, $matcher->matchBySimilarName($this->makeRequest('gin')));
    }

    public function testMatchBySimilarNameExactMatchWinsFirst(): void
    {
        $matcher = $this->makeMatcher();

        $this->assertSame(542, $matcher->matchBySimilarName($this->makeRequest('Cointreau')));
    }

    public function testMatchBySimilarNameIsCaseInsensitive(): void
    {
        $matcher = $this->makeMatcherWithNames('Blended scotch whisky');

        $this->assertSame(600, $matcher->matchBySimilarName($this->makeRequest('SCOTCH WHISKY')));
    }

    public function testMatchBySimilarNameReturnsNullWhenNoIngredientContainsAllWords(): void
    {
        $matcher = $this->makeMatcher();

        $this->assertNull($matcher->matchBySimilarName($this->makeRequest('Captin Morgon Spiced Gold')));
    }

    public function testMatchBySimilarNameNeverCreatesIngredient(): void
    {
        $matcher = $this->makeMatcher();

        $matcher->matchBySimilarName($this->makeRequest('Captin Morgon'));

        $this->assertSame([], $this->ingredientRepository->list(new BarId(65)));
    }

    public function testMatchOrCreateByNameStillCreatesOnMiss(): void
    {
        $matcher = $this->makeMatcher();

        $id = $matcher->matchOrCreateByName($this->makeRequest('New bottle'));

        $this->assertCount(1, $this->ingredientRepository->list(new BarId(65)));
        $this->assertSame(1, $id);
    }
}
