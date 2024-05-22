<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Export extends Model
{
    use HasFactory;
    use HasAuthors;
    use HasBarAwareScope;

    /**
     * @return BelongsTo<Bar, Export>
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

    public function withFilename(): self
    {
        $this->filename = sprintf('%s_%s_%s.zip', Carbon::now()->format('Ymd'), 'recipes', Str::random(8));

        return $this;
    }

    public function getFullPath(): string
    {
        $folderPath = storage_path(sprintf('bar-assistant/backups/'));

        return $folderPath . $this->filename;
    }

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
        ];
    }
}
