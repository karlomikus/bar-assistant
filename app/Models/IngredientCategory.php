<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IngredientCategory extends Model
{
    use HasFactory, HasBarAwareScope, HasAuthors;
}
