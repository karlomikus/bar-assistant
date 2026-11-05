<?php

declare(strict_types=1);

namespace Tests\Unit\GenAI;

use Tests\TestCase;
use Laravel\Ai\Files\Base64Image;
use Kami\Cocktail\GenAI\ShelfPhotoAgent;
use Laravel\Ai\Responses\StructuredAgentResponse;

class ShelfPhotoAgentTest extends TestCase
{
    public function testPromptWithImageAttachmentReturnsStructuredBottleNames(): void
    {
        config(['bar-assistant.ai.provider' => 'openai']);
        ShelfPhotoAgent::fake([
            ['bottles' => [
                ['name' => 'Cointreau'],
                ['name' => 'Tanqueray London Dry'],
            ]],
        ]);

        $response = new ShelfPhotoAgent()->prompt(
            'List the readable bottle names on this shelf photo.',
            [new Base64Image(base64_encode('fake-image-bytes'), 'image/webp')],
        );

        $this->assertInstanceOf(StructuredAgentResponse::class, $response);
        $this->assertSame(
            [['name' => 'Cointreau'], ['name' => 'Tanqueray London Dry']],
            $response['bottles'],
        );
    }

    public function testPromptCarriesImageAttachment(): void
    {
        config(['bar-assistant.ai.provider' => 'openai']);
        $gateway = ShelfPhotoAgent::fake([
            ['bottles' => [['name' => 'Cointreau']]],
        ])->preventStrayPrompts();

        new ShelfPhotoAgent()->prompt(
            'List the readable bottle names on this shelf photo.',
            [new Base64Image(base64_encode('fake-image-bytes'), 'image/webp')],
        );

        ShelfPhotoAgent::assertPrompted(function ($prompt) {
            return $prompt->attachments->count() === 1
                && $prompt->attachments->first() instanceof Base64Image;
        });
    }
}
