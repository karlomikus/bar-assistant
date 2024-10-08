<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('glasses', function (Blueprint $table) {
            $table->decimal('volume')->nullable();
            $table->string('volume_units')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('glasses', function (Blueprint $table) {
            $table->dropColumn('volume');
        });
        Schema::table('glasses', function (Blueprint $table) {
            $table->dropColumn('volume_units');
        });
    }
};
