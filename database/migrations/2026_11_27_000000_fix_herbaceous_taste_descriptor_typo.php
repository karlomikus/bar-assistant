<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if the descriptor was already renamed to avoid a unique constraint violation.
        if (DB::table('taste_descriptors')->where('normalized_name', 'herbaceous')->exists()) {
            return;
        }

        DB::table('taste_descriptors')
            ->where('normalized_name', 'herbacious')
            ->update([
                'name' => 'Herbaceous',
                'normalized_name' => 'herbaceous',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('taste_descriptors')->where('normalized_name', 'herbacious')->exists()) {
            return;
        }

        DB::table('taste_descriptors')
            ->where('normalized_name', 'herbaceous')
            ->update([
                'name' => 'Herbacious',
                'normalized_name' => 'herbacious',
                'updated_at' => now(),
            ]);
    }
};
