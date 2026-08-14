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

    public function test_resolves_base64_image_data_uri_contents_and_cleans_up_temporary_files(): void
    {
        $content = $this->getFakeImageContent('png');
        $temporaryFilesBefore = $this->temporaryImageFiles();
        $source = 'data:image/png;base64,' . base64_encode($content);

        $resolvedSource = (new ImageResolver())->resolveImageSource($source);

        $this->assertSame($content, $resolvedSource);
        $this->assertSame($temporaryFilesBefore, $this->temporaryImageFiles());
    }

    public function test_resolves_url_image_contents_and_cleans_up_temporary_files(): void
    {
        $temporaryFilesBefore = $this->temporaryImageFiles();

        $resolvedSource = (new ImageResolver())->resolveImageSource('https://barassistant.app/public/img/recipe-details.png');

        $this->assertNotNull($resolvedSource);
        $this->assertSame($temporaryFilesBefore, $this->temporaryImageFiles());
    }

    public function test_cleans_up_temporary_files_when_base64_image_validation_fails(): void
    {
        $temporaryFilesBefore = $this->temporaryImageFiles();

        try {
            (new ImageResolver())->resolveImageSource('data:image/png;base64,' . base64_encode('not an image'));
            $this->fail('An invalid image data URI must fail validation.');
        } catch (ValidationException) {
        }

        $this->assertSame($temporaryFilesBefore, $this->temporaryImageFiles());
    }

    public function test_resolves_invalid_url_image(): void
    {
        $this->expectException(ValidationException::class);

        (new ImageResolver())->resolveImageSource('file://my.local');
    }

    public function test_rejects_unsupported_image_source(): void
    {
        $this->expectException(ValidationException::class);

        (new ImageResolver())->resolveImageSource('not-an-image-source');
    }

    public function test_rejects_uploaded_file_that_is_not_an_image(): void
    {
        $image = UploadedFile::fake()->createWithContent('image.txt', 'not an image');

        $this->expectException(ValidationException::class);

        (new ImageResolver())->resolveImageSource($image);
    }

    /**
     * @return string[]
     */
    private function temporaryImageFiles(): array
    {
        $temporaryFiles = glob(sys_get_temp_dir() . '/bass*');

        if ($temporaryFiles === false) {
            return [];
        }

        sort($temporaryFiles);

        return $temporaryFiles;
    }
}
