<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Kami\Cocktail\Models\Concerns\IsExternalized;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Kami\Cocktail\Services\Image\ImageStorageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kami\Cocktail\Exceptions\ImageFileNotFoundException;

class Image extends BaseModel implements IsExternalized
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

    #[\Override]
    public function delete(): ?bool
    {
        app(ImageStorageService::class)->delete($this);

        return parent::delete();
    }

    public function getImageUrl(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        $disk = Storage::disk($this->disk);

        return $disk->url($this->file_path);
    }

    public function getImageThumbUrl(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return route('images.thumb', ['id' => $this->id], false);
    }

    public function getImageAsFileURI(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return 'file:///' . $this->getFileName();
    }

    public function getExternalId(): string
    {
        return $this->getFileName() ?? (string) $this->id;
    }

    public function getFileName(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return basename($this->file_path);
    }

    /**
     * @return MorphTo<Ingredient|Cocktail|Glass, $this>
     */
    public function imageable(): MorphTo
    {
        /** @phpstan-ignore-next-line */
        return $this->morphTo();
    }

    public function getPath(): string
    {
        $disk = Storage::disk($this->disk);

        if ($disk->exists($this->file_path)) {
            return $disk->path($this->file_path);
        }

        throw new ImageFileNotFoundException('Image not found at path: ' . $this->file_path);
    }

    /**
     * @return Collection<int, Image>
     */
    public function getAllBarImages(int $barId): Collection
    {
        $cocktailImages = $this->select('images.*')->where('imageable_type', Cocktail::class)
            ->join('cocktails', 'cocktails.id', '=', 'images.imageable_id')
            ->where('cocktails.bar_id', $barId)
            ->get();

        $ingredientImages = $this->select('images.*')->where('imageable_type', Ingredient::class)
            ->join('ingredients', 'ingredients.id', '=', 'images.imageable_id')
            ->where('ingredients.bar_id', $barId)
            ->get();

        $glassImages = $this->select('images.*')->where('imageable_type', Glass::class)
            ->join('glasses', 'glasses.id', '=', 'images.imageable_id')
            ->where('glasses.bar_id', $barId)
            ->get();

        return $cocktailImages->merge($ingredientImages)->merge($glassImages);
    }

    public function isTemporaryImage(): bool
    {
        return str_starts_with($this->file_path, 'temp/') || $this->imageable_id === null;
    }
}
