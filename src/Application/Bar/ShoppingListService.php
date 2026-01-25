<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Application\Bar\DTO\MemberShoppingListChangeRequest;
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

    public function addIngredientToMembersShoppingList(MemberShoppingListChangeRequest $request): void
    {
        $member = $this->memberRepository->findById(new MemberId($request->memberId));
        if ($member === null || $member->isTransient()) {
            throw new EntityNotFoundException('Bar member was not found');
        }

        $member->addIngredientToShoppingList(new IngredientId($request->ingredientId), $request->quantity);

        $this->memberRepository->save($member);
    }
}
