<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Bar;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\Member;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\MemberRole;
use BarAssistant\Domain\Cocktail\CocktailId;

final class MemberTest extends TestCase
{
    private function createMember(): Member
    {
        return Member::create(
            userId: new UserId(1),
            barId: new BarId(1),
            role: MemberRole::Admin,
        );
    }

    public function test_add_cocktail_to_favorites(): void
    {
        $member = $this->createMember();
        $cocktailId = new CocktailId(10);

        $member->addCocktailToFavorites($cocktailId);

        $this->assertCount(1, $member->getFavoriteCocktails());
        $this->assertTrue($member->isCocktailFavorited($cocktailId));
    }

    public function test_add_cocktail_to_favorites_is_idempotent(): void
    {
        $member = $this->createMember();
        $cocktailId = new CocktailId(10);

        $member->addCocktailToFavorites($cocktailId);
        $member->addCocktailToFavorites($cocktailId);

        $this->assertCount(1, $member->getFavoriteCocktails());
    }

    public function test_add_multiple_cocktails_to_favorites(): void
    {
        $member = $this->createMember();

        $member->addCocktailToFavorites(new CocktailId(10));
        $member->addCocktailToFavorites(new CocktailId(20));
        $member->addCocktailToFavorites(new CocktailId(30));

        $this->assertCount(3, $member->getFavoriteCocktails());
        $this->assertTrue($member->isCocktailFavorited(new CocktailId(10)));
        $this->assertTrue($member->isCocktailFavorited(new CocktailId(20)));
        $this->assertTrue($member->isCocktailFavorited(new CocktailId(30)));
    }

    public function test_remove_cocktail_from_favorites(): void
    {
        $member = $this->createMember();
        $cocktailId = new CocktailId(10);

        $member->addCocktailToFavorites($cocktailId);
        $member->removeCocktailFromFavorites($cocktailId);

        $this->assertCount(0, $member->getFavoriteCocktails());
        $this->assertFalse($member->isCocktailFavorited($cocktailId));
    }

    public function test_remove_non_favorited_cocktail_is_noop(): void
    {
        $member = $this->createMember();
        $cocktailId = new CocktailId(10);

        $member->removeCocktailFromFavorites($cocktailId);

        $this->assertCount(0, $member->getFavoriteCocktails());
    }

    public function test_remove_cocktail_preserves_other_favorites(): void
    {
        $member = $this->createMember();

        $member->addCocktailToFavorites(new CocktailId(10));
        $member->addCocktailToFavorites(new CocktailId(20));
        $member->addCocktailToFavorites(new CocktailId(30));

        $member->removeCocktailFromFavorites(new CocktailId(20));

        $this->assertCount(2, $member->getFavoriteCocktails());
        $this->assertTrue($member->isCocktailFavorited(new CocktailId(10)));
        $this->assertFalse($member->isCocktailFavorited(new CocktailId(20)));
        $this->assertTrue($member->isCocktailFavorited(new CocktailId(30)));
    }

    public function test_is_cocktail_favorited_returns_false_when_empty(): void
    {
        $member = $this->createMember();

        $this->assertFalse($member->isCocktailFavorited(new CocktailId(10)));
    }

    public function test_get_favorite_cocktails_returns_empty_array_by_default(): void
    {
        $member = $this->createMember();

        $this->assertSame([], $member->getFavoriteCocktails());
    }

    public function test_favorite_has_favorited_at_timestamp(): void
    {
        $member = $this->createMember();

        $member->addCocktailToFavorites(new CocktailId(10));

        $favorites = $member->getFavoriteCocktails();
        $this->assertNotNull($favorites[0]->favoritedAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $favorites[0]->favoritedAt);
    }

    public function test_fluent_interface_on_add_and_remove(): void
    {
        $member = $this->createMember();

        $result = $member
            ->addCocktailToFavorites(new CocktailId(10))
            ->addCocktailToFavorites(new CocktailId(20))
            ->removeCocktailFromFavorites(new CocktailId(10));

        $this->assertSame($member, $result);
        $this->assertCount(1, $member->getFavoriteCocktails());
        $this->assertTrue($member->isCocktailFavorited(new CocktailId(20)));
    }
}
