<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Concerns;

use Illuminate\Support\Str;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasImages
{
    /**
     * @return MorphMany<Image, $this>
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('sort');
    }

    public function getMainImageUrl(): ?string
    {
        return $this->getMainImage()?->getImageUrl();
    }

    public function getMainImage(): ?Image
    {
        return $this->images->sortBy('sort')->first() ?? null;
    }

    public function getMainImageThumbUrl(bool $absolute = true): ?string
    {
        if (!$this->getMainImage()) {
            return null;
        }

        return route('images.thumb', ['id' => $this->getMainImage()->id], $absolute);
    }

    public function deleteImages(): void
    {
        foreach ($this->images as $image) {
            $image->delete();
        }
    }

    /**
     * @param Collection<int, Image> $images
     */
    public function attachImages(Collection $images): void
    {
        $disk = Storage::disk('uploads');

        foreach ($images as $image) {
            if (!$image->isTemp()) { // Dont attach already attached images
                continue;
            }

            $oldFilePath = $image->file_path;
            $newFilePath = $this->getUploadPath() . $this->slug . '_' . Str::random(6) . '.' . $image->file_extension;

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
