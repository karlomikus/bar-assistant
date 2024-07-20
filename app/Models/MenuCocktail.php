<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuCocktail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'cocktail_id',
        'category_name',
        'sort',
        'price',
        'currency',
    ];

    /**
     * @return BelongsTo<Cocktail, MenuCocktail>
     */
    public function cocktail(): BelongsTo
    {
        return $this->belongsTo(Cocktail::class);
    }

    /**
     * @return BelongsTo<Menu, MenuCocktail>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
