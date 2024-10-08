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
        Schema::table('bar_memberships', function (Blueprint $table) {
            $table->boolean('use_parent_as_substitute')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bar_memberships', function (Blueprint $table) {
            $table->dropColumn('use_parent_as_substitute');
        });
    }
};
