<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Kami\Cocktail\Repository\CocktailRepository;

/**
 * @mixin \Kami\Cocktail\Models\Cocktail
 */
final class CocktailQueryFilter extends QueryBuilder
{
    public function __construct(CocktailRepository $cocktailRepo)
    {
        parent::__construct(Cocktail::query());

        $barMembership = $this->request->user()->getBarMembership(bar()->id);

        $useParentIngredientAsSubstitute = $barMembership->use_parent_as_substitute;

        $this
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('ingredient_name', 'ingredients.ingredient.name'),
                AllowedFilter::exact('ingredient_id', 'ingredients.ingredient.id'),
                AllowedFilter::exact('tag_id', 'tags.id'),
                AllowedFilter::exact('created_user_id'),
                AllowedFilter::exact('glass_id'),
                AllowedFilter::exact('cocktail_method_id'),
                AllowedFilter::callback('collection_id', function ($query, $value) use ($barMembership) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    $query->whereHas('collections', function ($query) use ($value, $barMembership) {
                        $query->whereIn('collections.id', $value)
                            ->join('bar_memberships', 'bar_memberships.id', '=', 'collections.bar_membership_id')
                            ->where(function ($query) use ($barMembership) {
                                $query->where('collections.bar_membership_id', $barMembership->id)->orWhere('is_bar_shared', true);
                            });
                    });
                }),
                AllowedFilter::callback('favorites', function ($query, $value) use ($barMembership) {
                    if ($value === true) {
                        $query->userFavorites($barMembership->id);
                    }
                }),
                AllowedFilter::callback('on_shelf', function ($query, $value) use ($cocktailRepo, $useParentIngredientAsSubstitute) {
                    if ($value === true) {
                        $query->whereIn('cocktails.id', $cocktailRepo->getCocktailsByIngredients(
                            $this->request->user()->getShelfIngredients(bar()->id)->pluck('ingredient_id')->toArray(),
                            useParentIngredientAsSubstitute: $useParentIngredientAsSubstitute,
                        ));
                    }
                }),
                AllowedFilter::callback('user_shelves', function ($query, $value) use ($cocktailRepo, $useParentIngredientAsSubstitute) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    $ingredients = DB::table('bar_memberships')
                        ->select('user_ingredients.ingredient_id')
                        ->join('user_ingredients', 'user_ingredients.bar_membership_id', '=', 'bar_memberships.id')
                        ->whereIn('bar_memberships.user_id', $value)
                        ->where('bar_memberships.bar_id', bar()->id)
                        ->where('bar_memberships.is_shelf_public', true)
                        ->get();

                    $query->whereIn('cocktails.id', $cocktailRepo->getCocktailsByIngredients(
                        $ingredients->pluck('ingredient_id')->toArray(),
                        useParentIngredientAsSubstitute: $useParentIngredientAsSubstitute,
                    ));
                }),
                AllowedFilter::callback('shelf_ingredients', function ($query, $value) use ($cocktailRepo, $useParentIngredientAsSubstitute) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    $query->whereIn('cocktails.id', $cocktailRepo->getCocktailsByIngredients(
                        $value,
                        useParentIngredientAsSubstitute: $useParentIngredientAsSubstitute
                    ));
                }),
                AllowedFilter::callback('is_public', function ($query, $value) {
                    if ($value === true) {
                        $query->whereNotNull('public_id');
                    }
                }),
                AllowedFilter::callback('user_rating_min', function ($query, $value) {
                    $query->where('user_rating', '>=', (int) $value);
                }),
                AllowedFilter::callback('user_rating_max', function ($query, $value) {
                    $query->where('user_rating', '<=', (int) $value);
                }),
                AllowedFilter::callback('average_rating_min', function ($query, $value) {
                    $query->where('average_rating', '>=', (int) $value);
                }),
                AllowedFilter::callback('average_rating_max', function ($query, $value) {
                    $query->where('average_rating', '<=', (int) $value);
                }),
                AllowedFilter::callback('abv_min', function ($query, $value) {
                    $query->where('abv', '>=', $value);
                }),
                AllowedFilter::callback('abv_max', function ($query, $value) {
                    $query->where('abv', '<=', $value);
                }),
                AllowedFilter::callback('main_ingredient_id', function ($query, $value) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    $query->whereIn('ci.ingredient_id', $value)->where('sort', '=', 1);
                }),
                AllowedFilter::callback('total_ingredients', function ($query, $value) {
                    $query->having('total_ingredients', '>=', (int) $value);
                }),
                AllowedFilter::callback('missing_ingredients', function ($query, $value) {
                    if ((int) $value >= 3) {
                        $query->having('missing_ingredients', '>=', (int) $value);
                    } else {
                        $query->having('missing_ingredients', (int) $value);
                    }
                }),
                AllowedFilter::callback('ignore_ingredients', function ($query, $value) use ($barMembership) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    $query->whereNotIn('cocktails.id', function ($query) use ($barMembership, $value) {
                        $query
                            ->select('cocktails.id')
                            ->from('cocktails')
                            ->where('cocktails.bar_id', $barMembership->bar_id)
                            ->join('cocktail_ingredients', 'cocktail_ingredients.cocktail_id', '=', 'cocktails.id')
                            ->whereIn('cocktail_ingredients.ingredient_id', $value);
                    });
                }),
            ])
            ->defaultSort('name')
            ->allowedSorts([
                'name',
                'created_at',
                'average_rating',
                'user_rating',
                'abv',
                'total_ingredients',
                'missing_ingredients',
                AllowedSort::callback('favorited_at', function ($query, bool $descending) use ($barMembership) {
                    $direction = $descending ? 'DESC' : 'ASC';

                    $query->leftJoin('cocktail_favorites AS cf', 'cf.cocktail_id', '=', 'cocktails.id')
                        ->where('cf.bar_membership_id', $barMembership->id)
                        ->orderBy('cf.updated_at', $direction);
                }),
            ])
            ->allowedIncludes(['glass', 'method', 'user', 'navigation', 'utensils', 'createdUser', 'updatedUser'])
            ->with('ingredients.ingredient', 'images', 'tags', 'ratings')
            ->selectRaw('cocktails.*, COUNT(ci.cocktail_id) AS total_ingredients, COUNT(ci.ingredient_id) - COUNT(ui.ingredient_id) AS missing_ingredients')
            ->leftJoin('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'cocktails.id')
            ->leftJoin('user_ingredients AS ui', function ($query) use ($barMembership) {
                $query->on('ui.ingredient_id', '=', 'ci.ingredient_id')->where('ui.bar_membership_id', $barMembership->id);
            })
            ->groupBy('cocktails.id')
            ->filterByBar()
            ->withRatings($this->request->user()->id);
    }
}
