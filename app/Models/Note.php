<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Note extends Model
{
    use HasFactory;

    /**
     * @return MorphTo<Cocktail|Model, Note>
     */
    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }
}
