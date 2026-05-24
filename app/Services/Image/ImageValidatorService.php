<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\File\File;

final readonly class ImageValidatorService
{
    public function getValidImageSource(string|UploadedFile $formImage): ?string
    {
        $imageSource = null;
        $imageFileRules = ['image' => 'image|max:51200'];

        if ($formImage instanceof UploadedFile) {
            Validator::make(['image' => $formImage], $imageFileRules)->validate();

            if ($sourceData = $formImage->get()) {
                $imageSource = $sourceData;
            }

            return $imageSource;
        }

        $tempFileName = null;
        $tempFileObject = null;

        try {
            if ($imageSource = file_get_contents($formImage)) {
                $tempFileName = tempnam(sys_get_temp_dir(), 'bass');

                if ($tempFileName !== false) {
                    file_put_contents($tempFileName, $imageSource);
                    $tempFileObject = new File($tempFileName);
                }
            } else {
                $imageSource = null;
            }
        } catch (Throwable) {
        }

        try {
            Validator::make(['image' => $tempFileObject], $imageFileRules)->validate();
        } finally {
            if ($tempFileName !== null && $tempFileName !== false && file_exists($tempFileName)) {
                unlink($tempFileName);
            }
        }

        return $imageSource;
    }
}
