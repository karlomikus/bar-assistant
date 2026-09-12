<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\TasteDescriptor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TasteDescriptorControllerTest extends TestCase
{
    use RefreshDatabase;

    private function headers(int $barId): array
    {
        return ['Bar-Assistant-Bar-Id' => (string) $barId];
    }

    public function test_list_returns_bar_descriptors_alphabetically(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        TasteDescriptor::factory()->for($membership->bar)->create(['name' => 'banana', 'normalized_name' => 'banana']);
        TasteDescriptor::factory()->for($membership->bar)->create(['name' => 'Apple', 'normalized_name' => 'apple']);
        TasteDescriptor::factory()->for($membership->bar)->create(['name' => 'cherry', 'normalized_name' => 'cherry']);

        $response = $this->getJson('/api/taste-descriptors', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertSame(['Apple', 'banana', 'cherry'], array_column($response->json('data'), 'name'));
    }

    public function test_filter_by_partial_name_is_case_insensitive(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        TasteDescriptor::factory()->for($membership->bar)->create(['name' => 'Smoky', 'normalized_name' => 'smoky']);
        TasteDescriptor::factory()->for($membership->bar)->create(['name' => 'Sweet', 'normalized_name' => 'sweet']);

        $response = $this->getJson('/api/taste-descriptors?filter[name]=smok', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertSame(['Smoky'], array_column($response->json('data'), 'name'));
    }

    public function test_descriptors_from_other_bars_are_excluded(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        TasteDescriptor::factory()->for($membership->bar)->create(['name' => 'Smoky', 'normalized_name' => 'smoky']);
        TasteDescriptor::factory()->for(Bar::factory()->create())->create(['name' => 'Sweet', 'normalized_name' => 'sweet']);

        $response = $this->getJson('/api/taste-descriptors', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertSame(['Smoky'], array_column($response->json('data'), 'name'));
    }

    public function test_non_member_is_denied(): void
    {
        $member = $this->setupBarMembership();
        $nonMember = $this->setupBarMembership();

        $this->actingAs($nonMember->user);

        $this->getJson('/api/taste-descriptors', $this->headers($member->bar_id))->assertForbidden();
    }

    public function test_unauthenticated_request_is_denied(): void
    {
        $membership = $this->setupBarMembership();

        $this->getJson('/api/taste-descriptors', $this->headers($membership->bar_id))->assertUnauthorized();
    }
}
