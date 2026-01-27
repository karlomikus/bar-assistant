<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bar;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\Member;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberRole;
use BarAssistant\Domain\Ingredient\IngredientId;
use Tests\Infrastructure\InMemoryMemberRepository;
use BarAssistant\Application\Bar\ShoppingListService;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Bar\DTO\MemberShoppingListChangeRequest;
use BarAssistant\Application\Bar\DTO\MemberShoppingListRemoveIngredientRequest;

final class ShoppingListServiceTest extends TestCase
{
    private InMemoryMemberRepository $memberRepository;
    private ShoppingListService $service;

    protected function setUp(): void
    {
        $this->memberRepository = new InMemoryMemberRepository([
            1 => Member::create(
                userId: new UserId(10),
                barId: new BarId(1),
                role: MemberRole::Admin
            )->setId(new MemberId(1)),
            2 => Member::create(
                userId: new UserId(11),
                barId: new BarId(1),
                role: MemberRole::General
            )->setId(new MemberId(2)),
        ]);

        $this->service = new ShoppingListService($this->memberRepository);
    }

    public function test_adds_ingredients_to_member_shopping_list(): void
    {
        $request = new MemberShoppingListChangeRequest(
            memberId: 1,
            ingredientQuantities: [
                100 => 500,
                101 => 750,
            ]
        );

        $this->service->addIngredientsToMemberShoppingList($request);

        $member = $this->memberRepository->findById(new MemberId(1));
        $this->assertNotNull($member);
        $this->assertCount(2, $member->getShoppingListIngredients());

        $shoppingList = $member->getShoppingListIngredients();
        $this->assertTrue($member->isIngredientOnShoppingList(new IngredientId(100)));
        $this->assertTrue($member->isIngredientOnShoppingList(new IngredientId(101)));

        // Verify quantities
        $ingredient100 = array_find($shoppingList, fn ($item) => $item->ingredientId->value === 100);
        $ingredient101 = array_find($shoppingList, fn ($item) => $item->ingredientId->value === 101);

        $this->assertNotNull($ingredient100);
        $this->assertNotNull($ingredient101);
        $this->assertSame(500, $ingredient100->quantity);
        $this->assertSame(750, $ingredient101->quantity);
    }

    public function test_adds_single_ingredient_to_shopping_list(): void
    {
        $request = new MemberShoppingListChangeRequest(
            memberId: 2,
            ingredientQuantities: [200 => 1000]
        );

        $this->service->addIngredientsToMemberShoppingList($request);

        $member = $this->memberRepository->findById(new MemberId(2));
        $this->assertNotNull($member);
        $this->assertCount(1, $member->getShoppingListIngredients());
        $this->assertTrue($member->isIngredientOnShoppingList(new IngredientId(200)));
    }

    public function test_replaces_ingredient_quantity_when_adding_duplicate(): void
    {
        // First add ingredient 100 with quantity 500
        $firstRequest = new MemberShoppingListChangeRequest(
            memberId: 1,
            ingredientQuantities: [100 => 500]
        );
        $this->service->addIngredientsToMemberShoppingList($firstRequest);

        // Then add the same ingredient with a different quantity
        $secondRequest = new MemberShoppingListChangeRequest(
            memberId: 1,
            ingredientQuantities: [100 => 750]
        );
        $this->service->addIngredientsToMemberShoppingList($secondRequest);

        $member = $this->memberRepository->findById(new MemberId(1));
        $this->assertNotNull($member);
        $this->assertCount(1, $member->getShoppingListIngredients());

        $shoppingList = $member->getShoppingListIngredients();
        $ingredient100 = array_find($shoppingList, fn ($item) => $item->ingredientId->value === 100);
        $this->assertNotNull($ingredient100);
        $this->assertSame(750, $ingredient100->quantity);
    }

    public function test_throws_exception_when_member_not_found(): void
    {
        $request = new MemberShoppingListChangeRequest(
            memberId: 999,
            ingredientQuantities: [100 => 500]
        );

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Bar member was not found');

        $this->service->addIngredientsToMemberShoppingList($request);
    }

    public function test_removes_ingredients_from_member_shopping_list(): void
    {
        // First add ingredients
        $addRequest = new MemberShoppingListChangeRequest(
            memberId: 1,
            ingredientQuantities: [
                100 => 500,
                101 => 750,
                102 => 1000,
            ]
        );
        $this->service->addIngredientsToMemberShoppingList($addRequest);

        // Then remove some
        $removeRequest = new MemberShoppingListRemoveIngredientRequest(
            memberId: 1,
            ingredientIds: [100, 102]
        );
        $this->service->removeIngredientsFromMemberShoppingList($removeRequest);

        $member = $this->memberRepository->findById(new MemberId(1));
        $this->assertNotNull($member);
        $this->assertCount(1, $member->getShoppingListIngredients());
        $this->assertFalse($member->isIngredientOnShoppingList(new IngredientId(100)));
        $this->assertTrue($member->isIngredientOnShoppingList(new IngredientId(101)));
        $this->assertFalse($member->isIngredientOnShoppingList(new IngredientId(102)));
    }

    public function test_removes_single_ingredient_from_shopping_list(): void
    {
        // First add ingredient
        $addRequest = new MemberShoppingListChangeRequest(
            memberId: 2,
            ingredientQuantities: [200 => 500]
        );
        $this->service->addIngredientsToMemberShoppingList($addRequest);

        // Then remove it
        $removeRequest = new MemberShoppingListRemoveIngredientRequest(
            memberId: 2,
            ingredientIds: [200]
        );
        $this->service->removeIngredientsFromMemberShoppingList($removeRequest);

        $member = $this->memberRepository->findById(new MemberId(2));
        $this->assertNotNull($member);
        $this->assertCount(0, $member->getShoppingListIngredients());
        $this->assertFalse($member->isIngredientOnShoppingList(new IngredientId(200)));
    }

    public function test_removes_all_ingredients_from_shopping_list(): void
    {
        // First add ingredients
        $addRequest = new MemberShoppingListChangeRequest(
            memberId: 1,
            ingredientQuantities: [
                100 => 500,
                101 => 750,
                102 => 1000,
            ]
        );
        $this->service->addIngredientsToMemberShoppingList($addRequest);

        // Remove all
        $removeRequest = new MemberShoppingListRemoveIngredientRequest(
            memberId: 1,
            ingredientIds: [100, 101, 102]
        );
        $this->service->removeIngredientsFromMemberShoppingList($removeRequest);

        $member = $this->memberRepository->findById(new MemberId(1));
        $this->assertNotNull($member);
        $this->assertCount(0, $member->getShoppingListIngredients());
    }

    public function test_removes_non_existent_ingredient_does_not_fail(): void
    {
        // Add one ingredient
        $addRequest = new MemberShoppingListChangeRequest(
            memberId: 2,
            ingredientQuantities: [200 => 500]
        );
        $this->service->addIngredientsToMemberShoppingList($addRequest);

        // Try to remove a non-existent ingredient along with an existing one
        $removeRequest = new MemberShoppingListRemoveIngredientRequest(
            memberId: 2,
            ingredientIds: [200, 999]
        );

        // This should not throw an exception
        $this->service->removeIngredientsFromMemberShoppingList($removeRequest);

        $member = $this->memberRepository->findById(new MemberId(2));
        $this->assertNotNull($member);
        $this->assertCount(0, $member->getShoppingListIngredients());
    }

    public function test_throws_exception_when_removing_from_non_existent_member(): void
    {
        $removeRequest = new MemberShoppingListRemoveIngredientRequest(
            memberId: 999,
            ingredientIds: [100]
        );

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Bar member was not found');

        $this->service->removeIngredientsFromMemberShoppingList($removeRequest);
    }

    public function test_empty_ingredient_list_to_add_does_not_fail(): void
    {
        $request = new MemberShoppingListChangeRequest(
            memberId: 1,
            ingredientQuantities: []
        );

        // Should not throw an exception
        $this->service->addIngredientsToMemberShoppingList($request);

        $member = $this->memberRepository->findById(new MemberId(1));
        $this->assertNotNull($member);
        $this->assertCount(0, $member->getShoppingListIngredients());
    }

    public function test_empty_ingredient_list_to_remove_does_not_fail(): void
    {
        // First add an ingredient
        $addRequest = new MemberShoppingListChangeRequest(
            memberId: 2,
            ingredientQuantities: [200 => 500]
        );
        $this->service->addIngredientsToMemberShoppingList($addRequest);

        // Then try to remove with empty list
        $removeRequest = new MemberShoppingListRemoveIngredientRequest(
            memberId: 2,
            ingredientIds: []
        );

        // Should not throw an exception
        $this->service->removeIngredientsFromMemberShoppingList($removeRequest);

        $member = $this->memberRepository->findById(new MemberId(2));
        $this->assertNotNull($member);
        $this->assertCount(1, $member->getShoppingListIngredients());
    }
}
