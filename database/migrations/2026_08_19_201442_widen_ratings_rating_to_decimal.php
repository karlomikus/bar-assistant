<?php

declare(strict_types=1);

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
            // Widen `rating` from smallInteger to DECIMAL(3,1) so 0.5-step values
            // (1.0, 1.5, ..., 5.0) can be stored. Existing integer rows cast cleanly
            // since every integer is a valid value on the 0.5 grid.
            $table->decimal('rating', 3, 1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->smallInteger('rating')->change();
        });
    }
};
