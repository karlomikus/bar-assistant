<?php

declare(strict_types=1);

namespace Tests\Feature\Rules;

use Illuminate\Contracts\Translation\Translator;
use Tests\TestCase;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Translation\PotentiallyTranslatedString;

class ResourceBelongsToBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes(): void
    {
        $membership = $this->setupBarMembership();

        $hasFailed = false;
        $failed = function () use (&$hasFailed): PotentiallyTranslatedString {
            $hasFailed = true;
            $translator = $this->getMockBuilder(Translator::class)->getMock();

            return new PotentiallyTranslatedString('', $translator);
        };

        $ingredients = Ingredient::factory()->for($membership->bar)->count(3)->create();

        $rule = new ResourceBelongsToBar($membership->bar->id, 'ingredients');
        $rule->validate('ingredients', $ingredients->pluck('id')->toArray(), $failed);

        $this->assertFalse($hasFailed);
    }

    public function test_validates_some_ids(): void
    {
        $membership = $this->setupBarMembership();

        $hasFailed = false;
        $failed = function () use (&$hasFailed): PotentiallyTranslatedString {
            $hasFailed = true;

            $translator = $this->getMockBuilder(Translator::class)->getMock();

            return new PotentiallyTranslatedString('', $translator);
        };

        $ingredients = Ingredient::factory()->for($membership->bar)->count(3)->create();

        $rule = new ResourceBelongsToBar($membership->bar->id, 'ingredients');
        $rule->validate('ingredients', $ingredients->pluck('id')->merge([100, 200])->toArray(), $failed);

        $this->assertTrue($hasFailed);
    }

    public function test_validation_fails(): void
    {
        $membership = $this->setupBarMembership();

        $hasFailed = false;
        $failed = function () use (&$hasFailed): PotentiallyTranslatedString {
            $hasFailed = true;

            $translator = $this->getMockBuilder(Translator::class)->getMock();

            return new PotentiallyTranslatedString('', $translator);
        };

        $ingredients = Ingredient::factory()->for($membership->bar)->count(3)->create();

        $rule = new ResourceBelongsToBar(2, 'ingredients');
        $rule->validate('ingredients', $ingredients->pluck('id')->toArray(), $failed);

        $this->assertTrue($hasFailed);
    }
}
