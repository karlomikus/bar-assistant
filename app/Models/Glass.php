<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Models\Concerns\HasImages;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Glass extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\GlassFactory> */
    use HasFactory;
    use HasBarAwareScope;
    use HasAuthors;
    use HasImages;

    public function getUploadPath(): string
    {
        return 'glasses/' . $this->bar_id . '/';
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name);
    }

    /**
     * @return Attribute<?float, ?float>
     */
    protected function volume(): Attribute
    {
        return Attribute::make(
            get: function (?float $value) {
                if ($value <= 0.0) {
                    return null;
                }

                return $value;
            },
            set: function (?float $value) {
                if ($value <= 0.0) {
                    return null;
                }

                return $value;
            },
        );
    }

    /**
     * @return HasMany<Cocktail, $this>
     */
    public function cocktails(): HasMany
    {
        return $this->hasMany(Cocktail::class);
    }

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

    public function delete(): bool
    {
        if (!empty(config('scout.driver'))) {
            $this->cocktails->each(fn ($cocktail) => $cocktail->searchable());
        }

        $this->deleteImages();

        return parent::delete();
    }
}
