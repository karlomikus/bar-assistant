<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(<<<SQL
            INSERT INTO bar_ingredients (bar_id, ingredient_id) 
            SELECT bar_id, ingredient_id FROM user_ingredients 
                JOIN bar_memberships ON user_ingredients.bar_membership_id = bar_memberships.id 
            WHERE user_id = (SELECT created_user_id FROM bars)
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DELETE FROM bar_ingredients');
    }
};
