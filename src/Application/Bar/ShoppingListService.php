<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Application\Bar\DTO\MemberShoppingListChangeRequest;
use BarAssistant\Application\Bar\DTO\MemberShoppingListRemoveIngredientRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Domain\Ingredient\IngredientId;

final readonly class ShoppingListService
{
    public function __construct(
        private MemberRepository $memberRepository,
    ) {
    }

    public function addIngredientsToMemberShoppingList(MemberShoppingListChangeRequest $request): void
    {
        $member = $this->memberRepository->findById(new MemberId($request->memberId));
        if ($member === null || $member->isTransient()) {
            throw new EntityNotFoundException('Bar member was not found');
        }

        foreach ($request->ingredientQuantities as $ingredientId => $quantity) {
            $member->addIngredientToShoppingList(new IngredientId($ingredientId), $quantity);
        }

        $this->memberRepository->save($member);
    }

    public function removeIngredientsFromMemberShoppingList(MemberShoppingListRemoveIngredientRequest $request): void
    {
        $member = $this->memberRepository->findById(new MemberId($request->memberId));
        if ($member === null || $member->isTransient()) {
            throw new EntityNotFoundException('Bar member was not found');
        }

        foreach ($request->ingredientIds as $ingredientId) {
            $member->removeIngredientFromShoppingList(new IngredientId($ingredientId));
        }

        $this->memberRepository->save($member);
    }
}
