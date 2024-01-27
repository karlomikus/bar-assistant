<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuCocktail extends Model
{
    use HasFactory;

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
