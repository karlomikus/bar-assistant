<?php
declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Kami\Cocktail\UpdateSiteSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cocktail extends Model
{
    use HasFactory, Searchable;

    protected static function booted()
    {
        static::saving(function ($cocktail) {
            $cocktail->slug = Str::slug($cocktail->name);
        });

        static::saved(function($cocktail) {
            UpdateSiteSearch::update($cocktail);
        });
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(CocktailIngredient::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function attachImages(Collection $images): void
    {
        $disk = Storage::disk('app_images');

        foreach($images as $image) {
            if ($image->imageable_id !== null) {
                continue;
            }

            $oldFilePath = $image->file_path;
            $newFilePath = 'cocktails/' . Str::slug($this->name) . '.' . $image->file_extension;

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

    public function latestImageFilePath(): ?string
    {
        return $this->images->first()->file_path ?? null;
    }

    public function getImageUrl(): string
    {
        $disk = Storage::disk('app_images');

        $filePath = $this->latestImageFilePath();

        if (!$filePath || !$disk->exists($filePath)) {
            return $disk->url('cocktails/no-image.jpg');
        }

        return $disk->url($filePath);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function deleteImages(): void
    {
        $disk = Storage::disk('app_images');

        foreach ($this->images as $image) {
            if ($disk->exists($image->file_path)) {
                $disk->delete($image->file_path);
            }
        }

        $this->images()->delete();
    }

    public function delete()
    {
        $this->deleteImages();

        return parent::delete();
    }

    public function toSiteSearchArray()
    {
        return [
            'key' => 'co_' . (string) $this->id,
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'image_url' => $this->getImageUrl(),
            'type' => 'cocktail',
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'source' => $this->source,
            'garnish' => $this->garnish,
            'image_url' => $this->getImageUrl(),
            'short_ingredients' => $this->ingredients->pluck('ingredient.name'),
            'tags' => $this->tags->pluck('name'),
            'date' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
