<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScrapeSsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrape_rejects_an_internal_image_url_in_supplied_recipe_html(): void
    {
        $membership = $this->setupBarMembership();
        $user = $membership->user;
        $this->assertNotNull($user);
        $this->actingAs($user);
        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        Http::fake();
        Http::preventStrayRequests();

        $imageUrl = 'http://127.0.0.1/ssrf-regression';
        $html = <<<'HTML'
            <script type="application/ld+json">
            {"@type":"Recipe","name":"SSRF regression","image":"http://127.0.0.1/ssrf-regression"}
            </script>
            HTML;

        $environment = $this->app->environment();
        $this->assertTrue(stream_wrapper_unregister('http'));

        try {
            $this->assertTrue(stream_wrapper_register('http', FakeInternalHttpStream::class));
            FakeInternalHttpStream::$requests = [];

            // Confirm the fixture intercepts the legacy file_get_contents path without network access.
            $this->assertSame(FakeInternalHttpStream::RESPONSE, file_get_contents($imageUrl));
            FakeInternalHttpStream::$requests = [];

            // Image fetching is normally bypassed in the testing environment.
            $this->app->instance('env', 'local');
            $response = $this->postJson('/api/import/scrape', [
                'source' => 'https://example.com/recipe',
                'html_content' => $html,
            ]);

            $response->assertBadRequest();
            $response->assertJsonPath('message', 'Scraping URLs with IP addresses is not allowed.');
            $this->assertStringNotContainsString(base64_encode(FakeInternalHttpStream::RESPONSE), (string) $response->getContent());
            $this->assertSame([], FakeInternalHttpStream::$requests);
            Http::assertNothingSent();
        } finally {
            $this->app->instance('env', $environment);
            stream_wrapper_restore('http');
        }
    }
}

class FakeInternalHttpStream
{
    public const string RESPONSE = 'internal-only-secret';

    /** @var list<string> */
    public static array $requests = [];

    public mixed $context;

    private int $offset = 0;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        self::$requests[] = $path;

        return true;
    }

    public function stream_read(int $count): string
    {
        $data = substr(self::RESPONSE, $this->offset, $count);
        $this->offset += strlen($data);

        return $data;
    }

    public function stream_eof(): bool
    {
        return $this->offset >= strlen(self::RESPONSE);
    }

    /** @return array<mixed> */
    public function stream_stat(): array
    {
        return [];
    }
}
