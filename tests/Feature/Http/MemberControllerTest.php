<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MemberControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );

        $this->setupBar();
    }

    public function test_list_member_response(): void
    {
        $users = User::factory()->count(9)->create();
        foreach ($users as $user) {
            DB::table('bar_memberships')->insert(['bar_id' => 1, 'user_id' => $user->id, 'user_role_id' => UserRoleEnum::General->value]);
        }

        $this->withHeader('Bar-Assistant-Bar-Id', '1');
        $response = $this->getJson('/api/members');

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );
    }

    public function test_list_member_forbidden_for_guest_member(): void
    {
        $membership = $this->setupBarMembership(UserRoleEnum::Guest);
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->getJson('/api/members');

        $response->assertForbidden();
    }

    public function test_show_member_response(): void
    {
        $user = User::factory()->create([
            'name' => 'Test'
        ]);
        DB::table('bar_memberships')->insert(['bar_id' => 1, 'user_id' => $user->id, 'user_role_id' => UserRoleEnum::General->value]);

        $this->withHeader('Bar-Assistant-Bar-Id', '1');
        $response = $this->getJson('/api/members/' . $user->id);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->where('data.name', 'Test')
                ->etc()
        );
    }

    public function test_show_member_forbidden_for_guest_member(): void
    {
        $membership = $this->setupBarMembership(UserRoleEnum::Guest);
        $member = BarMembership::factory()->for($membership->bar)->create([
            'user_role_id' => UserRoleEnum::General->value,
        ]);
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->getJson('/api/members/' . $member->user_id);

        $response->assertForbidden();
    }

    public function test_add_existing_user_as_member_response(): void
    {
        $user = User::factory()->create();
        $this->withHeader('Bar-Assistant-Bar-Id', '1');

        $response = $this->postJson('/api/members', [
            'email' => $user->email,
            'role_id' => UserRoleEnum::Admin->value,
        ]);

        $response->assertCreated();
        $this->assertNotEmpty($response->headers->get('Location'));
    }

    public function test_add_member_forbidden_for_guest_member(): void
    {
        $membership = $this->setupBarMembership(UserRoleEnum::Guest);
        $user = User::factory()->create();
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->postJson('/api/members', [
            'email' => $user->email,
            'role_id' => UserRoleEnum::Admin->value,
        ]);

        $response->assertForbidden();
    }

    public function test_add_member_forbidden_without_active_subscription(): void
    {
        config(['bar-assistant.enable_billing' => true]);

        $membership = $this->setupBarMembership();
        $user = User::factory()->create();
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->postJson('/api/members', [
            'email' => $user->email,
            'role_id' => UserRoleEnum::Admin->value,
        ]);

        $response->assertForbidden();
    }

    public function test_update_member_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $guestMember = BarMembership::factory()->for($membership->bar)->create([
            'user_role_id' => UserRoleEnum::Guest->value
        ]);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->putJson('/api/members/' . $guestMember->user_id, [
            'role_id' => UserRoleEnum::Admin->value,
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('bar_memberships', [
            'user_id' => $guestMember->user_id,
            'bar_id' => $membership->bar_id,
            'user_role_id' => UserRoleEnum::Admin->value,
        ]);
    }

    public function test_update_member_forbidden_for_guest_member(): void
    {
        $membership = $this->setupBarMembership();

        $guestMember = BarMembership::factory()->for($membership->bar)->create([
            'user_role_id' => UserRoleEnum::Guest->value,
        ]);
        $targetMember = BarMembership::factory()->for($membership->bar)->create([
            'user_role_id' => UserRoleEnum::General->value,
        ]);

        $this->actingAs($guestMember->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->putJson('/api/members/' . $targetMember->user_id, [
            'role_id' => UserRoleEnum::Admin->value,
        ]);

        $response->assertForbidden();
    }

    public function test_delete_member_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $guestMember = BarMembership::factory()->for($membership->bar)->create([
            'user_role_id' => UserRoleEnum::Guest->value
        ]);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->delete('/api/members/' . $guestMember->user_id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('bar_memberships', [
            'user_id' => $guestMember->user_id,
            'bar_id' => $membership->bar_id,
            'user_role_id' => UserRoleEnum::Guest->value,
        ]);
    }

    public function test_delete_user_own_membership_response(): void
    {

        $membership = $this->setupBarMembership();

        $guestMember = BarMembership::factory()->for($membership->bar)->create([
            'user_role_id' => UserRoleEnum::Guest->value
        ]);

        $this->actingAs($guestMember->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->delete('/api/members/' . $guestMember->user_id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('bar_memberships', [
            'user_id' => $guestMember->user_id,
            'bar_id' => $membership->bar_id,
            'user_role_id' => UserRoleEnum::Guest->value,
        ]);
    }

    public function test_delete_unowned_membership_response(): void
    {
        $membership = $this->setupBarMembership();

        $guestMember = BarMembership::factory()->for($membership->bar)->create([
            'user_role_id' => UserRoleEnum::Guest->value
        ]);

        $this->actingAs($guestMember->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->delete('/api/members/' . $membership->user_id);

        $response->assertForbidden();
    }
}
