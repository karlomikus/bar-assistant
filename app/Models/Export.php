<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Export extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ExportFactory> */
    use HasFactory;
    use HasAuthors;
    use HasBarAwareScope;

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

    public function delete(): bool
    {
        if (File::exists($this->getFullPath())) {
            File::delete($this->getFullPath());
        }

        return parent::delete();
    }

    public function markAsDone(): self
    {
        $this->is_done = true;
        $this->save();

        return $this;
    }

    public static function generateFilename(string $exportTypeName = 'recipes'): string
    {
        return sprintf('%s_%s_%s.zip', $exportTypeName, Carbon::now()->format('Ymd'), Str::random(8));
    }

    public function getFullPath(): string
    {
        return Storage::disk('exports')->path($this->bar_id . '/' . $this->filename);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
        ];
    }
}
