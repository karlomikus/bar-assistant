<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TasteDescriptorControllerTest extends TestCase
{
    use RefreshDatabase;

    private function headers(int $barId): array
    {
        return ['Bar-Assistant-Bar-Id' => (string) $barId];
    }

    public function test_list_returns_descriptors_alphabetically(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $response = $this->getJson('/api/taste-descriptors', $this->headers($membership->bar_id));

        $response->assertOk();
        $names = array_column($response->json('data'), 'name');
        $sortedNames = $names;
        sort($sortedNames, SORT_NATURAL | SORT_FLAG_CASE);

        $this->assertNotEmpty($names);
        $this->assertSame($sortedNames, $names);
    }

    public function test_filter_by_partial_name_is_case_insensitive(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $response = $this->getJson('/api/taste-descriptors?filter[name]=smok', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertSame(['Smoky'], array_column($response->json('data'), 'name'));
    }

    public function test_static_descriptors_are_shared_between_bars(): void
    {
        $firstMembership = $this->setupBarMembership();
        $secondMembership = $this->setupBarMembership();
        $this->actingAs($firstMembership->user);

        $firstResponse = $this->getJson('/api/taste-descriptors', $this->headers($firstMembership->bar_id));
        $firstResponse->assertOk();

        $this->actingAs($secondMembership->user);
        $secondResponse = $this->getJson('/api/taste-descriptors', $this->headers($secondMembership->bar_id));
        $secondResponse->assertOk();

        $this->assertSame($firstResponse->json('data'), $secondResponse->json('data'));
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
