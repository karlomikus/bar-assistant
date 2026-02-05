<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\Member;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberRole;
use Kami\Cocktail\Models\UserShoppingList;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Domain\Bar\ShoppingListItem;
use BarAssistant\Domain\Ingredient\IngredientId;
use Kami\Cocktail\Models\BarMembership as Model;

final class EloquentMemberRepository implements MemberRepository
{
    public function save(Member $member): Member
    {
        $model = Model::findOrNew($member->getId()?->value);
        $model->bar_id = $member->getBarId()->value;
        $model->user_id = $member->getUserId()->value;
        $model->user_role_id = match ($member->getRole()) {
            MemberRole::Admin => 1,
            MemberRole::Moderator => 2,
            MemberRole::General => 3,
            MemberRole::Guest => 4,
        };
        $model->save();

        $model->shoppingListIngredients()->delete();
        foreach ($member->getShoppingListIngredients() as $shoppingListIngredient) {
            $shoppingListItemModel = new UserShoppingList();
            $shoppingListItemModel->ingredient_id = $shoppingListIngredient->ingredientId;
            $shoppingListItemModel->bar_membership_id = $model->id;
            $shoppingListItemModel->quantity = $shoppingListIngredient->quantity;

            $model->shoppingListIngredients()->save($shoppingListItemModel);
        }

        return self::map($model);
    }

    public function delete(Member $member): void
    {
        throw new \Exception('Not implemented');
    }

    public function findById(MemberId $memberId): ?Member
    {
        $model = Model::find($memberId->value);

        return self::map($model);
    }

    public function findUserInBar(UserId $userId, BarId $barId): ?Member
    {
        throw new \Exception('Not implemented');
    }

    private static function map(Model $model): Member
    {
        $shoppingListIngredients = [];
        foreach ($model->shoppingListIngredients as $modelShoppingListIngredient) {
            $shoppingListIngredients[] = ShoppingListItem::create(new IngredientId($modelShoppingListIngredient->ingredient_id), $modelShoppingListIngredient->quantity);
        }

        $domainObject = Member::create(
            userId: new UserId($model->user_id),
            barId: new BarId($model->bar_id),
            role: MemberRole::fromString($model->role->name),
            shoppingListIngredients: $shoppingListIngredients,
        )->setId(new MemberId($model->id));

        return $domainObject;
    }
}
