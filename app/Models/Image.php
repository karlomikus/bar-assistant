<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Intervention\Image\ImageManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Image extends Model
{
    use HasFactory;

    public function delete(): ?bool
    {
        $disk = Storage::disk('uploads');

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

        $disk = Storage::disk('uploads');

        return $disk->url($this->file_path);
    }

    public function getImageDataURI(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        $manager = ImageManager::imagick();
        $disk = Storage::disk('uploads');

        return $manager->read($disk->path($this->file_path))->encode()->toDataUri();
    }

    /**
     * @return MorphTo<Ingredient|Cocktail|Model, Image>
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getPath(): string
    {
        $disk = Storage::disk('uploads');

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

    public function isTemp(): bool
    {
        return str_starts_with($this->file_path, 'temp/') || $this->imageable_id === null;
    }

    public function getThumb(): string
    {
        $disk = Storage::disk('uploads');
        $manager = ImageManager::imagick();

        return $manager->read($disk->get($this->file_path))->coverDown(400, 400)->toJpeg(50)->toString();
    }
}
