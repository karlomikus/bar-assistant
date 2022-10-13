<?php
declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CocktailFavorite extends Model
{
    use HasFactory;

    public function cocktail()
    {
        return $this->belongsTo(Cocktail::class);
    }
}
