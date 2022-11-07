<?php
declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Kami\Cocktail\SearchActions;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasFactory, Searchable, HasImages, HasSlug;

    private $appImagesDir = 'ingredients/';
    private $missingImageFileName = 'no-image.png'; // TODO: WEBP

    protected $fillable = [
        'name',
        'strength',
        'description',
        'history',
        'origin',
        'color',
        'ingredient_category_id',
        'parent_ingredient_id',
    ];

    protected static function booted()
    {
        static::saved(function($ing) {
            SearchActions::update($ing);
        });

        static::deleted(function($ing) {
            SearchActions::delete($ing);
        });
    }

    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IngredientCategory::class, 'ingredient_category_id', 'id');
    }

    public function cocktails(): BelongsToMany
    {
        return $this->belongsToMany(Cocktail::class, CocktailIngredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function delete()
    {
        $this->deleteImages();

        return parent::delete();
    }

    public function toSiteSearchArray()
    {
        return [
            'key' => 'in_' . (string) $this->id,
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'image_url' => $this->getImageUrl(),
            'type' => 'ingredient',
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'image_url' => $this->getImageUrl(),
            'description' => $this->description,
            'category' => $this->category->name,
        ];
    }
}
