<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Kami\Cocktail\Services\VersionCheckService;

class VersionCheckServiceTest extends TestCase
{
    public function test_latest_version_is_cached(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response(['tag_name' => 'v6.9.1']),
        ]);

        $service = new VersionCheckService();

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame('v6.9.1', $service->getLatestVersion());
        }

        Http::assertSentCount(1);

        $this->travel(61)->minutes();
        $this->assertSame('v6.9.1', $service->getLatestVersion());

        Http::assertSentCount(2);
    }

    public function test_failed_lookup_is_cached_for_a_shorter_time(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response(['message' => 'API rate limit exceeded'], 403),
        ]);

        $service = new VersionCheckService();

        for ($i = 0; $i < 5; $i++) {
            $this->assertNull($service->getLatestVersion());
        }

        Http::assertSentCount(1);

        $this->travel(16)->minutes();
        $this->assertNull($service->getLatestVersion());

        Http::assertSentCount(2);
    }

    public function test_version_endpoint_does_not_call_github_on_every_request(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response(['tag_name' => 'v6.9.1']),
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/server/version')->assertOk();
        }

        Http::assertSentCount(1);
    }
}
