<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Image extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ImageFactory> */
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

    public function getImageAsFileURI(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return 'file:///' . $this->getFileName();
    }

    public function getFileName(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return match ($this->imageable_type) {
            Cocktail::class => str_replace('cocktails/' . $this->imageable->bar_id . '/', '', $this->file_path),
            Ingredient::class => str_replace('ingredients/' . $this->imageable->bar_id . '/', '', $this->file_path),
            default => null,
        };
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
}
