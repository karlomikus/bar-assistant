<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use BarAssistant\Domain\Bar\BarId;
use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Bar\Member;
use BarAssistant\Domain\User\UserId;
use Kami\Cocktail\Models\Ingredient;
use BarAssistant\Domain\Bar\MemberRole;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentMemberRepository;
use Kami\Cocktail\Models\BarMembership as ModelMembership;

final class EloquentMemberRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_and_finds_member_with_related_state(): void
    {
        $membership = $this->setupBarMembership();
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $member = Member::create(
            userId: new UserId($user->id),
            barId: new BarId($membership->bar_id),
            role: MemberRole::General,
        );
        $member->addIngredientToShoppingList(new IngredientId($ingredient->id), 2);
        $member->addCocktailToFavorites(new CocktailId($cocktail->id));

        $repository = new EloquentMemberRepository();
        $savedMember = $repository->save($member);

        $this->assertNotNull($savedMember->getId());
        $this->assertDatabaseHas('bar_memberships', [
            'id' => $savedMember->getId()?->value,
            'bar_id' => $membership->bar_id,
            'user_id' => $user->id,
            'user_role_id' => 3,
        ]);
        $this->assertDatabaseHas('user_shopping_lists', [
            'bar_membership_id' => $savedMember->getId()?->value,
            'ingredient_id' => $ingredient->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('cocktail_favorites', [
            'bar_membership_id' => $savedMember->getId()?->value,
            'cocktail_id' => $cocktail->id,
        ]);

        $foundMember = $repository->findUserInBar(new UserId($user->id), new BarId($membership->bar_id));

        $this->assertNotNull($foundMember);
        $this->assertSame(MemberRole::General, $foundMember->getRole());
        $this->assertCount(1, $foundMember->getShoppingListIngredients());
        $this->assertCount(1, $foundMember->getFavoriteCocktails());

        $repository->delete($savedMember);

        $this->assertDatabaseMissing('bar_memberships', ['id' => $savedMember->getId()?->value]);
    }

    public function test_it_deletes_many_memberships_by_user_id(): void
    {
        $membership = $this->setupBarMembership();
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $firstMembership = ModelMembership::factory()->for($targetUser)->for($membership->bar)->create();
        $secondMembership = ModelMembership::factory()->for($targetUser)->create();
        $otherMembership = ModelMembership::factory()->for($otherUser)->create();

        $repository = new EloquentMemberRepository();
        $repository->deleteManyByUserId(new UserId($targetUser->id));

        $this->assertDatabaseMissing('bar_memberships', ['id' => $firstMembership->id]);
        $this->assertDatabaseMissing('bar_memberships', ['id' => $secondMembership->id]);
        $this->assertDatabaseHas('bar_memberships', ['id' => $otherMembership->id]);
    }
}
