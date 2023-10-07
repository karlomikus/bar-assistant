<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use Tests\TestCase;
use Laravel\Scout\EngineManager;
use Kami\Cocktail\Search\MeilisearchActions;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeilisearchActionsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_getBarSearchApiKey(): void
    {
        $search = $this->getActions();

        $jwt = $search->getBarSearchApiKey(72);
        $contents = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $jwt)[1]))));

        $this->assertNotNull($jwt);
        $this->assertSame('bar_id = 72', $contents->searchRules->cocktails->filter);
        $this->assertSame('bar_id = 72', $contents->searchRules->ingredients->filter);
    }

    public function test_isAvailable(): void
    {
        $search = $this->getActions();

        $this->assertTrue($search->isAvailable());
    }

    public function test_getVersion(): void
    {
        $search = $this->getActions();

        $this->assertNotNull($search->getVersion());
    }

    public function test_getHost(): void
    {
        $search = $this->getActions();

        $this->assertNotNull($search->getHost());
    }

    private function getActions(): MeilisearchActions
    {
        $engineManager = resolve(EngineManager::class);

        return new MeilisearchActions($engineManager->engine());
    }
}
