<?php
declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasImages
{
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function latestImageFilePath(): ?string
    {
        return $this->images->first()->file_path ?? null;
    }

    public function getImageUrl(): string
    {
        $disk = Storage::disk('app_images');

        $filePath = $this->latestImageFilePath();

        if (!$filePath || !$disk->exists($filePath)) {
            return $disk->url($this->appImagesDir . $this->missingImageFileName);
        }

        return $disk->url($filePath);
    }

    public function deleteImages(): void
    {
        foreach ($this->images as $image) {
            $image->delete();
        }
    }

    public function attachImages(Collection $images): void
    {
        $disk = Storage::disk('app_images');

        foreach($images as $image) {
            if ($image->imageable_id !== null) {
                continue;
            }

            $oldFilePath = $image->file_path;
            $newFilePath = $this->appImagesDir . Str::slug($this->name) . '.' . $image->file_extension;

            if ($disk->exists($oldFilePath)) {
                $disk->move($oldFilePath, $newFilePath);

                $image->file_path = $newFilePath;
                $image->save();
            } else {
                $image->delete();
            }
        }

        $this->images()->saveMany($images);
    }
}
