<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            // Add the new bar_membership_id column
            $table->foreignId('bar_membership_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Resolve the bar_membership matching both the rater's user_id and the bar of the rated cocktail.
        DB::statement("
            UPDATE ratings
            SET bar_membership_id = (
                SELECT bm.id
                FROM bar_memberships bm
                JOIN cocktails c ON c.id = ratings.rateable_id
                WHERE bm.user_id = ratings.user_id
                  AND bm.bar_id = c.bar_id
            )
            WHERE rateable_type = 'Kami\\Cocktail\\Models\\Cocktail'
        ");

        // The new bar_membership_id FK cascades on delete; ratings whose membership was deleted are
        // orphaned and unrecoverable once user_id is dropped, so remove them before enforcing NOT NULL.
        DB::table('ratings')->whereNull('bar_membership_id')->delete();

        // Drop the old unique constraint
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'rateable_id', 'rateable_type']);
        });

        // Drop the user_id foreign key and column
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // Make bar_membership_id NOT NULL and add new unique constraint
        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('bar_membership_id')->nullable(false)->change();
            $table->unique(['bar_membership_id', 'rateable_id', 'rateable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
