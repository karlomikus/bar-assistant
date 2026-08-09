<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Image;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Kami\Cocktail\Services\Image\ImageResolver;

class ImageResolverTest extends TestCase
{
    public function test_resolves_uploaded_image_contents(): void
    {
        $content = $this->getFakeImageContent('png');
        $image = UploadedFile::fake()->createWithContent('image.png', $content);

        $source = (new ImageResolver())->resolveImageSource($image);

        $this->assertSame($content, $source);
    }

    public function test_resolves_base64_image_data_uri_contents(): void
    {
        $content = $this->getFakeImageContent('png');
        $source = 'data:image/png;base64,' . base64_encode($content);

        $resolvedSource = (new ImageResolver())->resolveImageSource($source);

        $this->assertSame($content, $resolvedSource);
    }

    public function test_resolves_valid_url_image(): void
    {
        $resolvedSource = (new ImageResolver())->resolveImageSource('https://barassistant.app/public/img/q-calc.png');

        $this->assertNotNull($resolvedSource);
    }

    public function test_resolves_invalid_url_image(): void
    {
        $this->expectException(ValidationException::class);

        (new ImageResolver())->resolveImageSource('file://my.local');
    }

    public function test_returns_null_for_unsupported_image_source(): void
    {
        $source = (new ImageResolver())->resolveImageSource('not-an-image-source');

        $this->assertNull($source);
    }

    public function test_rejects_uploaded_file_that_is_not_an_image(): void
    {
        $image = UploadedFile::fake()->createWithContent('image.txt', 'not an image');

        $this->expectException(ValidationException::class);

        (new ImageResolver())->resolveImageSource($image);
    }
}
