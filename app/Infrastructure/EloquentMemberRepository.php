<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use DateTimeImmutable;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\Member;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberRole;
use Kami\Cocktail\Models\UserShoppingList;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Bar\CocktailFavorite;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Domain\Bar\ShoppingListItem;
use BarAssistant\Domain\Ingredient\IngredientId;
use Kami\Cocktail\Models\BarMembership as Model;
use Kami\Cocktail\Models\CocktailFavorite as CocktailFavoriteModel;

final class EloquentMemberRepository implements MemberRepository
{
    public function save(Member $member): Member
    {
        $model = Model::findOrNew($member->getId()?->value);
        $model->bar_id = $member->getBarId()->value;
        $model->user_id = $member->getUserId()->value;
        $model->user_role_id = match ($member->getRole()) {
            MemberRole::Admin => 1,
            MemberRole::General => 3,
            MemberRole::Guest => 4,
        };
        $model->save();

        $model->shoppingListIngredients()->delete();
        foreach ($member->getShoppingListIngredients() as $shoppingListIngredient) {
            $shoppingListItemModel = new UserShoppingList();
            $shoppingListItemModel->ingredient_id = $shoppingListIngredient->ingredientId->value;
            $shoppingListItemModel->bar_membership_id = $model->id;
            $shoppingListItemModel->quantity = $shoppingListIngredient->quantity;

            $model->shoppingListIngredients()->save($shoppingListItemModel);
        }

        $model->cocktailFavorites()->delete();
        foreach ($member->getFavoriteCocktails() as $cocktailFavorite) {
            $cocktailFavoriteModel = new CocktailFavoriteModel();
            $cocktailFavoriteModel->cocktail_id = $cocktailFavorite->cocktailId->value;
            $cocktailFavoriteModel->bar_membership_id = $model->id;
            $cocktailFavoriteModel->created_at = $cocktailFavorite->favoritedAt->format('Y-m-d H:i:s');

            $model->cocktailFavorites()->save($cocktailFavoriteModel);
        }

        return self::map($model);
    }

    public function delete(Member $member): void
    {
        Model::destroy($member->getId()?->value);
    }

    public function findById(MemberId $memberId): ?Member
    {
        $model = Model::find($memberId->value);
        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function findUserInBar(UserId $userId, BarId $barId): ?Member
    {
        $model = Model::where('bar_id', $barId->value)->where('user_id', $userId->value)->first();
        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function deleteManyByUserId(UserId $userId): void
    {
        Model::where('user_id', $userId->value)->delete();
    }

    private static function map(Model $model): Member
    {
        $shoppingListIngredients = [];
        foreach ($model->shoppingListIngredients as $modelShoppingListIngredient) {
            $shoppingListIngredients[] = ShoppingListItem::create(new IngredientId($modelShoppingListIngredient->ingredient_id), $modelShoppingListIngredient->quantity);
        }

        $cocktailFavorites = [];
        foreach ($model->cocktailFavorites as $modelCocktailFavorite) {
            $cocktailFavorites[] = CocktailFavorite::createWithTimestamp(
                cocktailId: new CocktailId($modelCocktailFavorite->cocktail_id),
                favoritedAt: new DateTimeImmutable((string) $modelCocktailFavorite->created_at),
            );
        }

        $domainObject = Member::create(
            userId: new UserId($model->user_id),
            barId: new BarId($model->bar_id),
            role: MemberRole::fromString($model->role->name),
            shoppingListIngredients: $shoppingListIngredients,
            cocktailFavorites: $cocktailFavorites,
        )->setId(new MemberId($model->id));

        return $domainObject;
    }
}
