<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Kami\Cocktail\Search\SearchActionsAdapter;
use Kami\Cocktail\Search\SearchActionsContract;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // TODO:
        // Moving to new schema will take too much time
        // Easiest way is to export/backup all old data
        // Drop all current data
        // Offer import from previous version when creating a new bar
        // Maybe import from .sqlite file?

        /** @var SearchActionsContract */
        $searchActions = app(SearchActionsAdapter::class)->getActions();

        DB::statement('PRAGMA foreign_keys = OFF');

        // Remove unused columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('search_api_key');
        });

        // Remove default user
        DB::table('users')->delete(1);

        // Create a new default bar
        DB::table('bars')->insert([
            ['id' => 1, 'name' => 'My bar', 'search_driver_api_key' => $searchActions->getPublicApiKey()],
        ]);

        $users = DB::table('users')->get();
        $memberships = [];
        foreach ($users as $user) {
            $memberships[] = [
                'bar_id' => 1,
                'user_id' => $user->id,
                'user_role_id' => 1,
            ];
        }
        DB::table('bar_memberships')->insert($memberships);

        Schema::table('cocktails', function (Blueprint $table) {
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        });
        DB::table('cocktails')->update(['bar_id' => 1]);

        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        });
        DB::table('ingredients')->update(['bar_id' => 1]);

        DB::statement('PRAGMA foreign_keys = ON');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not planned yet...
    }
};
