<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bar;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\Member;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberRole;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Application\Bar\FavoriteService;
use Tests\Infrastructure\InMemoryMemberRepository;
use BarAssistant\Application\Bar\DTO\FavoriteRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

final class FavoriteServiceTest extends TestCase
{
    private InMemoryMemberRepository $memberRepository;
    private FavoriteService $service;

    protected function setUp(): void
    {
        $this->memberRepository = new InMemoryMemberRepository([
            1 => Member::create(
                userId: new UserId(10),
                barId: new BarId(1),
                role: MemberRole::Admin,
            )->setId(new MemberId(1)),
            2 => Member::create(
                userId: new UserId(11),
                barId: new BarId(1),
                role: MemberRole::General,
            )->setId(new MemberId(2)),
        ]);

        $this->service = new FavoriteService($this->memberRepository);
    }

    public function test_toggle_favorite_adds_favorite_when_not_favorited(): void
    {
        $request = new FavoriteRequest(
            memberId: 1,
            cocktailId: 100,
        );

        $result = $this->service->toggleFavorite($request);

        $this->assertSame(100, $result->cocktailId);
        $this->assertTrue($result->isFavorited);

        $member = $this->memberRepository->findById(new MemberId(1));
        $this->assertNotNull($member);
        $this->assertTrue($member->isCocktailFavorited(new CocktailId(100)));
    }

    public function test_toggle_favorite_removes_favorite_when_already_favorited(): void
    {
        // First, favorite the cocktail
        $request = new FavoriteRequest(
            memberId: 1,
            cocktailId: 100,
        );
        $this->service->toggleFavorite($request);

        // Toggle again to remove
        $result = $this->service->toggleFavorite($request);

        $this->assertSame(100, $result->cocktailId);
        $this->assertFalse($result->isFavorited);
        $this->assertNull($result->favoritedAt);

        $member = $this->memberRepository->findById(new MemberId(1));
        $this->assertNotNull($member);
        $this->assertFalse($member->isCocktailFavorited(new CocktailId(100)));
    }

    public function test_toggle_favorite_throws_exception_for_non_existent_member(): void
    {
        $request = new FavoriteRequest(
            memberId: 999,
            cocktailId: 100,
        );

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Member not found.');

        $this->service->toggleFavorite($request);
    }

    public function test_toggle_favorite_is_member_scoped(): void
    {
        // Member 1 favorites cocktail 100
        $this->service->toggleFavorite(new FavoriteRequest(
            memberId: 1,
            cocktailId: 100,
        ));

        // Member 2 should not have it favorited
        $member2 = $this->memberRepository->findById(new MemberId(2));
        $this->assertNotNull($member2);
        $this->assertFalse($member2->isCocktailFavorited(new CocktailId(100)));

        // Member 1 should have it favorited
        $member1 = $this->memberRepository->findById(new MemberId(1));
        $this->assertNotNull($member1);
        $this->assertTrue($member1->isCocktailFavorited(new CocktailId(100)));
    }

    public function test_toggle_favorite_multiple_cocktails(): void
    {
        $this->service->toggleFavorite(new FavoriteRequest(memberId: 1, cocktailId: 100));
        $this->service->toggleFavorite(new FavoriteRequest(memberId: 1, cocktailId: 200));
        $this->service->toggleFavorite(new FavoriteRequest(memberId: 1, cocktailId: 300));

        $member = $this->memberRepository->findById(new MemberId(1));
        $this->assertNotNull($member);
        $this->assertCount(3, $member->getFavoriteCocktails());
        $this->assertTrue($member->isCocktailFavorited(new CocktailId(100)));
        $this->assertTrue($member->isCocktailFavorited(new CocktailId(200)));
        $this->assertTrue($member->isCocktailFavorited(new CocktailId(300)));
    }

    public function test_toggle_removes_only_specified_cocktail(): void
    {
        $this->service->toggleFavorite(new FavoriteRequest(memberId: 1, cocktailId: 100));
        $this->service->toggleFavorite(new FavoriteRequest(memberId: 1, cocktailId: 200));

        // Remove cocktail 100
        $this->service->toggleFavorite(new FavoriteRequest(memberId: 1, cocktailId: 100));

        $member = $this->memberRepository->findById(new MemberId(1));
        $this->assertNotNull($member);
        $this->assertCount(1, $member->getFavoriteCocktails());
        $this->assertFalse($member->isCocktailFavorited(new CocktailId(100)));
        $this->assertTrue($member->isCocktailFavorited(new CocktailId(200)));
    }
}
