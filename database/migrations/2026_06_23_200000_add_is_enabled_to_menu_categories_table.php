<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(true)->after('sort');
        });
    }

    public function down(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->dropColumn('is_enabled');
        });
    }
};
