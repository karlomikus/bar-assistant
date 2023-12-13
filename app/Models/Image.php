<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Intervention\Image\Image as InterventionImage;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Intervention\Image\Facades\Image as ImageProcessor;

class Image extends Model
{
    use HasFactory;

    public function delete(): ?bool
    {
        $disk = config('bar-assistant.use_s3_uploads') ? Storage::disk('uploads_s3') : Storage::disk('uploads');
        ;

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

        $disk = config('bar-assistant.use_s3_uploads') ? Storage::disk('uploads_s3') : Storage::disk('uploads');

        return $disk->url($this->file_path);
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
        $disk = config('bar-assistant.use_s3_uploads') ? Storage::disk('uploads_s3') : Storage::disk('uploads');

        return ImageProcessor::make($disk->path($this->file_path));
    }

    public function getPath(): string
    {
        $disk = config('bar-assistant.use_s3_uploads') ? Storage::disk('uploads_s3') : Storage::disk('uploads');

        if ($disk->exists($this->file_path)) {
            return $disk->path($this->file_path);
        }

        throw new \Exception('Image not found');
    }

    /**
     * @return Collection<int, Image>
     */
    public function getAllBarImages(int $barId): Collection
    {
        $cocktailImages = $this->where('imageable_type', Cocktail::class)
            ->join('cocktails', 'cocktails.id', '=', 'images.imageable_id')
            ->where('cocktails.bar_id', $barId)
            ->get();

        $ingredientImages = $this->where('imageable_type', Ingredient::class)
            ->join('ingredients', 'ingredients.id', '=', 'images.imageable_id')
            ->where('ingredients.bar_id', $barId)
            ->get();

        return $cocktailImages->merge($ingredientImages);
    }
}
