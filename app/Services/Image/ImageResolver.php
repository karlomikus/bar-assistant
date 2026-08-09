<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

final readonly class ImageResolver
{
    public function resolveImageSource(string|UploadedFile $formImage): ?string
    {
        $imageFileRules = ['image' => 'image|max:51200'];

        if ($formImage instanceof UploadedFile) {
            Log::debug('Resolving image source from uploaded file');

            Validator::make(['image' => $formImage], $imageFileRules)->validate();

            if ($sourceData = $formImage->get()) {
                return $sourceData;
            }

            return null;
        }

        // Allow URLs as image sources, but validate them first
        if (is_string($formImage) && filter_var($formImage, FILTER_VALIDATE_URL)) {
            Log::debug('Resolving image source from URL', ['url' => $formImage]);

            Validator::make(['image_url' => $formImage], ['image_url' => 'url:http,https'])->validate();

            $tempFileObject = null;
            try {
                if ($imageSource = file_get_contents($formImage)) {
                    $tempFileObject = $this->createTemporaryUploadedFile($imageSource, pathinfo($formImage, PATHINFO_EXTENSION));
                } else {
                    $imageSource = null;
                }
            } catch (Throwable $e) {
                Log::error('Failed to fetch image from URL', ['url' => $formImage, 'error' => $e->getMessage()]);
            }

            Validator::make(['image' => $tempFileObject], $imageFileRules)->validate();

            return $imageSource ?? null;
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $formImage, $type)) {
            Log::debug('Resolving image source from base64 data URI');

            $data = substr($formImage, strpos($formImage, ',') + 1);
            $content = base64_decode($data);
            $extension = strtolower($type[1]); // e.g., png, jpeg
            $tempFileObject = $this->createTemporaryUploadedFile($content, $extension);

            Validator::make(['image' => $tempFileObject], $imageFileRules)->validate();

            return $tempFileObject->get();
        }

        return null;
    }

    private function createTemporaryUploadedFile(string $content, string $extension): UploadedFile
    {
        $tempFileName = tempnam(sys_get_temp_dir(), 'bass') . '.' . $extension;
        file_put_contents($tempFileName, $content);

        return new UploadedFile(
            path: $tempFileName,
            originalName: basename($tempFileName),
            mimeType: mime_content_type($tempFileName) ?: null,
            error: null,
            test: true
        );
    }
}
