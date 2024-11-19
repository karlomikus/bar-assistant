<?php

declare(strict_types=1);

namespace Kami\Cocktail\Exports;

use Kami\Cocktail\Models\MenuCocktail;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * @implements \Maatwebsite\Excel\Concerns\WithMapping<\Kami\Cocktail\Models\MenuCocktail>
 */
class MenusExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    public function __construct(public readonly int $barId)
    {
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<MenuCocktail>
     */
    public function query()
    {
        return MenuCocktail::query()
            ->with('cocktail.ingredients.ingredient')
            ->join('menus', 'menus.id', '=', 'menu_cocktails.menu_id')
            ->where('menus.bar_id', $this->barId);
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        return [
            'cocktail',
            'ingredients',
            'category',
            'price',
            'currency',
            'full_price',
        ];
    }

    /**
     * @return array<mixed>
     */
    public function map($menuCocktail): array
    {
        return [
            e(preg_replace("/\s+/u", " ", $menuCocktail->cocktail->name)),
            e($menuCocktail->cocktail->getIngredientNames()->implode(', ')),
            e($menuCocktail->category_name),
            $menuCocktail->getMoney()->getAmount()->toFloat(),
            $menuCocktail->getMoney()->getCurrency()->getCurrencyCode(),
            (string) $menuCocktail->getMoney(),
        ];
    }
}
