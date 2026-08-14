<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Menu\Menu;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Menu\MenuId;
use Kami\Cocktail\Models\Ingredient;
use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Menu\MenuItem;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\Bar as ModelBar;
use BarAssistant\Domain\Menu\MenuCategory;
use Kami\Cocktail\Models\Menu as ModelMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentMenuRepository;
use Kami\Cocktail\Models\MenuCategory as ModelMenuCategory;

final class EloquentMenuRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_menu_with_mixed_categories_and_items(): void
    {
        $membership = $this->setupBarMembership();
        $bar = $this->barFromMembership($membership);
        $bar->slug = 'evening-menu';
        $bar->save();

        $cocktail = Cocktail::factory()->for($bar)->create();
        $ingredient = Ingredient::factory()->for($bar)->create();

        $menu = Menu::createWithCategories(
            id: new MenuId('evening-menu'),
            barId: new BarId($membership->bar_id),
            categories: [
                MenuCategory::createWithItems(
                    name: Name::fromString('Cocktails'),
                    sortIndex: 1,
                    items: [
                        MenuItem::forCocktail(
                            cocktailId: new \BarAssistant\Domain\Cocktail\CocktailId($cocktail->id),
                            price: Price::createFromMinor(1250, 'USD'),
                            sortIndex: 2,
                            barInventoryAware: true,
                        ),
                    ],
                ),
                MenuCategory::createWithItems(
                    name: Name::fromString('Ingredients'),
                    sortIndex: 2,
                    items: [
                        MenuItem::forIngredient(
                            ingredientId: new \BarAssistant\Domain\Ingredient\IngredientId($ingredient->id),
                            price: Price::createFromMinor(450, 'EUR'),
                            sortIndex: 1,
                            barInventoryAware: false,
                        ),
                    ],
                ),
            ],
        )->enable();

        $repository = new EloquentMenuRepository();
        $storedMenu = $repository->save($menu);

        /** @var ModelMenu $menuModel */
        $menuModel = ModelMenu::query()->where('bar_id', $membership->bar_id)->firstOrFail();
        $cocktailCategory = ModelMenuCategory::query()->where('menu_id', $menuModel->id)->where('name', 'Cocktails')->firstOrFail();
        $ingredientCategory = ModelMenuCategory::query()->where('menu_id', $menuModel->id)->where('name', 'Ingredients')->firstOrFail();

        $this->assertDatabaseCount('menus', 1);
        $this->assertDatabaseCount('menu_categories', 2);
        $this->assertDatabaseCount('menu_cocktails', 1);
        $this->assertDatabaseCount('menu_ingredients', 1);

        $this->assertDatabaseHas('menus', [
            'id' => $menuModel->id,
            'bar_id' => $membership->bar_id,
            'is_enabled' => true,
        ]);
        $this->assertDatabaseHas('menu_categories', [
            'id' => $cocktailCategory->id,
            'menu_id' => $menuModel->id,
            'name' => 'Cocktails',
            'sort' => 1,
        ]);
        $this->assertDatabaseHas('menu_categories', [
            'id' => $ingredientCategory->id,
            'menu_id' => $menuModel->id,
            'name' => 'Ingredients',
            'sort' => 2,
        ]);
        $this->assertDatabaseHas('menu_cocktails', [
            'menu_category_id' => $cocktailCategory->id,
            'cocktail_id' => $cocktail->id,
            'price' => 1250,
            'currency' => 'USD',
            'sort' => 2,
            'is_bar_inventory_aware' => true,
        ]);
        $this->assertDatabaseHas('menu_ingredients', [
            'menu_category_id' => $ingredientCategory->id,
            'ingredient_id' => $ingredient->id,
            'price' => 450,
            'currency' => 'EUR',
            'sort' => 1,
            'is_bar_inventory_aware' => false,
        ]);

        $this->assertTrue($storedMenu->isEnabled());
        $this->assertSame('evening-menu', $storedMenu->getId()->value);
        $this->assertCount(2, $storedMenu->getCategories());
        $this->assertSame('Cocktails', $storedMenu->getCategories()[0]->getName()->toString());
        $this->assertSame('Ingredients', $storedMenu->getCategories()[1]->getName()->toString());
    }

    public function test_it_replaces_existing_categories_and_items_when_updating_menu(): void
    {
        $membership = $this->setupBarMembership();
        $bar = $this->barFromMembership($membership);
        $bar->slug = 'house-menu';
        $bar->save();

        $originalCocktail = Cocktail::factory()->for($bar)->create();
        $replacementIngredient = Ingredient::factory()->for($bar)->create();

        $repository = new EloquentMenuRepository();
        $repository->save(Menu::createWithCategories(
            id: new MenuId('house-menu'),
            barId: new BarId($membership->bar_id),
            categories: [
                MenuCategory::createWithItems(
                    name: Name::fromString('Original category'),
                    sortIndex: 0,
                    items: [
                        MenuItem::forCocktail(
                            cocktailId: new \BarAssistant\Domain\Cocktail\CocktailId($originalCocktail->id),
                            price: Price::createFromMinor(900, 'EUR'),
                            sortIndex: 0,
                        ),
                    ],
                ),
            ],
        ));

        $updatedMenu = Menu::createWithCategories(
            id: new MenuId('house-menu'),
            barId: new BarId($membership->bar_id),
            categories: [
                MenuCategory::createWithItems(
                    name: Name::fromString('Replacement category'),
                    sortIndex: 1,
                    items: [
                        MenuItem::forIngredient(
                            ingredientId: new \BarAssistant\Domain\Ingredient\IngredientId($replacementIngredient->id),
                            price: Price::createFromMinor(375, 'GBP'),
                            sortIndex: 3,
                            barInventoryAware: true,
                        ),
                    ],
                ),
            ],
        )->enable();

        $repository->save($updatedMenu);

        /** @var ModelMenu $menuModel */
        $menuModel = ModelMenu::query()->where('bar_id', $membership->bar_id)->firstOrFail();
        $replacementCategory = ModelMenuCategory::query()
            ->where('menu_id', $menuModel->id)
            ->where('name', 'Replacement category')
            ->firstOrFail();

        $this->assertDatabaseCount('menus', 1);
        $this->assertDatabaseCount('menu_categories', 1);
        $this->assertDatabaseCount('menu_cocktails', 0);
        $this->assertDatabaseCount('menu_ingredients', 1);

        $this->assertDatabaseMissing('menu_categories', [
            'menu_id' => $menuModel->id,
            'name' => 'Original category',
        ]);
        $this->assertDatabaseMissing('menu_cocktails', [
            'cocktail_id' => $originalCocktail->id,
        ]);
        $this->assertDatabaseHas('menu_ingredients', [
            'menu_category_id' => $replacementCategory->id,
            'ingredient_id' => $replacementIngredient->id,
            'price' => 375,
            'currency' => 'GBP',
            'sort' => 3,
            'is_bar_inventory_aware' => true,
        ]);
    }

    public function test_it_generates_bar_slug_when_saving_enabled_menu(): void
    {
        $membership = $this->setupBarMembership();
        $bar = $this->barFromMembership($membership);
        $bar->slug = null;
        $bar->save();

        $menu = Menu::create(
            id: new MenuId('temporary-id'),
            barId: new BarId($membership->bar_id),
        )->enable();

        $repository = new EloquentMenuRepository();
        $storedMenu = $repository->save($menu);

        $bar->refresh();

        $this->assertNotNull($bar->slug);
        $this->assertSame($bar->slug, $storedMenu->getId()->value);
    }

    public function test_it_finds_menu_by_bar_id_and_maps_sorted_categories_and_items(): void
    {
        $membership = $this->setupBarMembership();
        $bar = $this->barFromMembership($membership);
        $bar->slug = 'public-menu';
        $bar->save();

        $menu = ModelMenu::factory()->for($bar)->create(['is_enabled' => true]);
        $laterCategory = ModelMenuCategory::create([
            'menu_id' => $menu->id,
            'name' => 'Later',
            'sort' => 20,
        ]);
        $earlierCategory = ModelMenuCategory::create([
            'menu_id' => $menu->id,
            'name' => 'Earlier',
            'sort' => 10,
        ]);

        $cocktail = Cocktail::factory()->for($bar)->create();
        $ingredient = Ingredient::factory()->for($bar)->create();

        DB::table('menu_cocktails')->insert([
            'menu_category_id' => $earlierCategory->id,
            'cocktail_id' => $cocktail->id,
            'sort' => 2,
            'price' => 990,
            'currency' => null,
            'is_bar_inventory_aware' => 1,
        ]);
        DB::table('menu_ingredients')->insert([
            'menu_category_id' => $earlierCategory->id,
            'ingredient_id' => $ingredient->id,
            'sort' => 1,
            'price' => 250,
            'currency' => 'USD',
            'is_bar_inventory_aware' => 0,
        ]);

        $repository = new EloquentMenuRepository();
        $foundMenu = $repository->findByBarId(new BarId($membership->bar_id));

        $this->assertNotNull($foundMenu);
        $this->assertSame('public-menu', $foundMenu->getId()->value);
        $this->assertTrue($foundMenu->isEnabled());
        $this->assertCount(2, $foundMenu->getCategories());
        $this->assertSame('Earlier', $foundMenu->getCategories()[0]->getName()->toString());
        $this->assertSame('Later', $foundMenu->getCategories()[1]->getName()->toString());

        $items = $foundMenu->getCategories()[0]->getItems();
        $this->assertCount(2, $items);
        $this->assertTrue($items[0]->isIngredient());
        $this->assertSame($ingredient->id, $items[0]->getIngredientId()?->value);
        $this->assertSame(250, $items[0]->getPrice()->getAsMinor());
        $this->assertSame('USD', $items[0]->getPrice()->getCurrency());
        $this->assertFalse($items[0]->isBarInventoryAware());

        $this->assertTrue($items[1]->isCocktail());
        $this->assertSame($cocktail->id, $items[1]->getCocktailId()?->value);
        $this->assertSame(990, $items[1]->getPrice()->getAsMinor());
        $this->assertSame('EUR', $items[1]->getPrice()->getCurrency());
        $this->assertTrue($items[1]->isBarInventoryAware());
    }

    public function test_it_finds_menu_by_slug_based_id(): void
    {
        $membership = $this->setupBarMembership();
        $bar = $this->barFromMembership($membership);
        $bar->slug = 'tasting-menu';
        $bar->save();

        ModelMenu::factory()->for($bar)->create();

        $repository = new EloquentMenuRepository();

        $this->assertNotNull($repository->findById(new MenuId('tasting-menu')));
        $this->assertNull($repository->findById(new MenuId('missing-menu')));
    }

    private function barFromMembership(BarMembership $membership): ModelBar
    {
        $this->assertNotNull($membership->bar);

        return $membership->bar;
    }

    public function test_is_enabled_defaults_to_true_when_not_explicitly_set(): void
    {
        $membership = $this->setupBarMembership();
        $bar = $this->barFromMembership($membership);
        $bar->slug = 'default-category-menu';
        $bar->save();

        $repository = new EloquentMenuRepository();
        $repository->save(Menu::createWithCategories(
            id: new MenuId('default-category-menu'),
            barId: new BarId($membership->bar_id),
            categories: [
                MenuCategory::create(
                    name: Name::fromString('Classics'),
                    sortIndex: 0,
                ),
            ],
        ));

        $this->assertDatabaseHas('menu_categories', [
            'name' => 'Classics',
            'is_enabled' => true,
        ]);
    }

    public function test_it_persists_and_reads_back_disabled_category(): void
    {
        $membership = $this->setupBarMembership();
        $bar = $this->barFromMembership($membership);
        $bar->slug = 'draft-menu';
        $bar->save();

        $repository = new EloquentMenuRepository();
        $repository->save(Menu::createWithCategories(
            id: new MenuId('draft-menu'),
            barId: new BarId($membership->bar_id),
            categories: [
                MenuCategory::createWithItems(
                    name: Name::fromString('Hidden Category'),
                    sortIndex: 0,
                    items: [],
                    isEnabled: false,
                ),
            ],
        ));

        $this->assertDatabaseHas('menu_categories', [
            'name' => 'Hidden Category',
            'is_enabled' => false,
        ]);

        $foundMenu = $repository->findByBarId(new BarId($membership->bar_id));
        $this->assertNotNull($foundMenu);
        $this->assertCount(1, $foundMenu->getCategories());
        $this->assertFalse($foundMenu->getCategories()[0]->isEnabled());
    }

    public function test_it_preserves_is_enabled_when_replacing_categories(): void
    {
        $membership = $this->setupBarMembership();
        $bar = $this->barFromMembership($membership);
        $bar->slug = 'seasonal-menu';
        $bar->save();

        $repository = new EloquentMenuRepository();

        // First save: all enabled
        $repository->save(Menu::createWithCategories(
            id: new MenuId('seasonal-menu'),
            barId: new BarId($membership->bar_id),
            categories: [
                MenuCategory::create(Name::fromString('Summer'), sortIndex: 0, isEnabled: true),
                MenuCategory::create(Name::fromString('Fall'), sortIndex: 1, isEnabled: true),
            ],
        ));

        // Replace: one disabled, one still enabled
        $repository->save(Menu::createWithCategories(
            id: new MenuId('seasonal-menu'),
            barId: new BarId($membership->bar_id),
            categories: [
                MenuCategory::create(Name::fromString('Summer'), sortIndex: 0, isEnabled: false),
                MenuCategory::create(Name::fromString('Winter'), sortIndex: 1, isEnabled: true),
            ],
        ));

        $this->assertDatabaseCount('menu_categories', 2);
        $this->assertDatabaseMissing('menu_categories', ['name' => 'Fall']);
        $this->assertDatabaseHas('menu_categories', ['name' => 'Summer', 'is_enabled' => false]);
        $this->assertDatabaseHas('menu_categories', ['name' => 'Winter', 'is_enabled' => true]);

        $foundMenu = $repository->findByBarId(new BarId($membership->bar_id));
        $this->assertNotNull($foundMenu);
        $categories = $foundMenu->getCategories();
        $this->assertCount(2, $categories);
        $this->assertFalse($categories[0]->isEnabled());
        $this->assertSame('Summer', $categories[0]->getName()->toString());
        $this->assertTrue($categories[1]->isEnabled());
        $this->assertSame('Winter', $categories[1]->getName()->toString());
    }
}
