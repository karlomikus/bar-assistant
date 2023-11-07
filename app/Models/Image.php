<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image as InterventionImage;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Intervention\Image\Facades\Image as ImageProcessor;

class Image extends Model
{
    use HasFactory;

    public function delete(): ?bool
    {
        $disk = Storage::disk('bar-assistant');

        if ($disk->exists($this->file_path)) {
            $disk->delete($this->file_path);
        }

        return parent::delete();
    }

    public function getImageUrl(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::disk('bar-assistant')->url($this->file_path);
    }

    /**
     * @return MorphTo<Ingredient|Cocktail|Model, Image>
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function asInterventionImage(): InterventionImage
    {
        $disk = Storage::disk('bar-assistant');

        return ImageProcessor::make($disk->path($this->file_path));
    }

    public function getPath(): string
    {
        $disk = Storage::disk('bar-assistant');

        if ($disk->exists($this->file_path)) {
            return $disk->path($this->file_path);
        }

        throw new \Exception('Image not found');
    }
}
