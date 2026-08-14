<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use RuntimeException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

final readonly class ImageResolver
{
    private const array IMAGE_FILE_RULES = ['image' => 'image|max:51200'];

    public function resolveImageSource(string|UploadedFile $formImage): ?string
    {
        if ($formImage instanceof UploadedFile) {
            Log::debug('Resolving image source from uploaded file');

            Validator::make(['image' => $formImage], self::IMAGE_FILE_RULES)->validate();

            if ($sourceData = $formImage->get()) {
                return $sourceData;
            }

            return null;
        }

        // Allow URLs as image sources, but validate them first
        if (filter_var($formImage, FILTER_VALIDATE_URL)) {
            Log::debug('Resolving image source from URL', ['url' => $formImage]);

            Validator::make(['image_url' => $formImage], ['image_url' => 'url:http,https'])->validate();

            try {
                if ($imageSource = file_get_contents($formImage)) {
                    return $this->resolveTemporaryImageSource(
                        content: $imageSource,
                        extension: pathinfo($formImage, PATHINFO_EXTENSION),
                    );
                }
            } catch (Throwable $e) {
                Log::error('Failed to fetch image from URL', ['url' => $formImage, 'error' => $e->getMessage()]);
            }

            Validator::make(['image' => null], self::IMAGE_FILE_RULES)->validate();

            return null;
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $formImage, $type)) {
            Log::debug('Resolving image source from base64 data URI');

            $data = substr($formImage, strpos($formImage, ',') + 1);
            $content = base64_decode($data, strict: true);

            if ($content === false) {
                return null;
            }

            return $this->resolveTemporaryImageSource(
                content: $content,
                extension: strtolower($type[1]),
            );
        }

        Validator::make(['image' => null], self::IMAGE_FILE_RULES)->validate();

        return null;
    }

    private function createTemporaryUploadedFile(string $content, string $extension): UploadedFile
    {
        $tempFileName = tempnam(sys_get_temp_dir(), 'bass');

        if ($tempFileName === false) {
            throw new RuntimeException('Unable to create temporary image file.');
        }

        try {
            if (file_put_contents($tempFileName, $content) === false) {
                throw new RuntimeException('Unable to write temporary image file.');
            }

            return new UploadedFile(
                path: $tempFileName,
                originalName: 'image.' . $extension,
                mimeType: mime_content_type($tempFileName) ?: null,
                error: null,
                test: true
            );
        } catch (Throwable $e) {
            $this->deleteTemporaryFile($tempFileName);

            throw $e;
        }
    }

    private function resolveTemporaryImageSource(string $content, string $extension): ?string
    {
        $tempFileObject = $this->createTemporaryUploadedFile($content, $extension);

        try {
            Validator::make(['image' => $tempFileObject], self::IMAGE_FILE_RULES)->validate();

            return $tempFileObject->get() ?: null;
        } finally {
            $this->deleteTemporaryFile($tempFileObject->getPathname());
        }
    }

    private function deleteTemporaryFile(string $path): void
    {
        if (is_file($path) && !unlink($path)) {
            Log::warning('Unable to delete temporary image file', ['path' => $path]);
        }
    }
}
