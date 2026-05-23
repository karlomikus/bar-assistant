<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        $legacyInventoryRows = DB::table('user_ingredients')
            ->join('bar_memberships', 'user_ingredients.bar_membership_id', '=', 'bar_memberships.id')
            ->select(
                'user_ingredients.bar_membership_id',
                'user_ingredients.ingredient_id',
                'bar_memberships.user_id',
            )
            ->orderBy('user_ingredients.bar_membership_id')
            ->get()
            ->groupBy('bar_membership_id');

        if ($legacyInventoryRows->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($legacyInventoryRows): void {
            foreach ($legacyInventoryRows as $barMembershipId => $membershipRows) {
                $inventoryId = DB::table('member_inventories')
                    ->where('bar_membership_id', $barMembershipId)
                    ->where('name', 'My Shelf')
                    ->value('id');

                if ($inventoryId === null) {
                    $inventoryId = DB::table('member_inventories')->insertGetId([
                        'bar_membership_id' => $barMembershipId,
                        'name' => 'My Shelf',
                    ]);
                }

                $existingIngredientIds = DB::table('member_inventory_ingredients')
                    ->where('member_inventory_id', $inventoryId)
                    ->pluck('ingredient_id')
                    ->all();

                $ingredientRows = [];
                foreach ($membershipRows->pluck('ingredient_id')->unique()->all() as $ingredientId) {
                    if (in_array($ingredientId, $existingIngredientIds, true)) {
                        continue;
                    }

                    $ingredientRows[] = [
                        'member_inventory_id' => $inventoryId,
                        'ingredient_id' => $ingredientId,
                    ];
                }

                if ($ingredientRows !== []) {
                    DB::table('member_inventory_ingredients')->insert($ingredientRows);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('member_inventory_ingredients')->delete();
        DB::table('member_inventories')->delete();
    }
};
