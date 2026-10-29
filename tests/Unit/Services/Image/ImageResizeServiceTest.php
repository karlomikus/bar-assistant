<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Image;

use Tests\TestCase;
use Jcupitt\Vips\Image as Vips;
use Kami\Cocktail\Services\Image\ImageResizeService;

class ImageResizeServiceTest extends TestCase
{
    public function test_resize_preserves_alpha_channel_through_webp_encode(): void
    {
        // The bundled AI bottle reference image is an RGBA PNG with transparent regions
        $fileContent = file_get_contents(resource_path('ai/no-ingredient.png'));
        $source = Vips::newFromBuffer($fileContent);
        $this->assertSame(4, $source->bands);

        $resized = ImageResizeService::resizeImageTo($fileContent);

        $buffer = $resized->writeToBuffer('.webp', ['Q' => 85]);
        $decoded = Vips::newFromBuffer($buffer);

        $this->assertSame(4, $decoded->bands);
        $this->assertSame(0, (int) $decoded->extract_band(3)->min(), 'Alpha channel must keep fully transparent pixels');
    }
}
