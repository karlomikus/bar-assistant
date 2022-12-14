<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Image extends Model
{
    use HasFactory;

    public function delete(): ?bool
    {
        $disk = Storage::disk('app_images');

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

        return Storage::disk('app_images')->url($this->file_path);
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
