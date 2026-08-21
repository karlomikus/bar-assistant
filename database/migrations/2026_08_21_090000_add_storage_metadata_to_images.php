<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table): void {
            $table->string('disk')->default('uploads')->after('file_extension');
            $table->enum('storage_origin', ['owned', 'catalog'])->default('owned')->after('disk');
        });

        DB::table('images')->update([
            'disk' => 'uploads',
            'storage_origin' => 'owned',
        ]);
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table): void {
            $table->dropColumn(['disk', 'storage_origin']);
        });
    }
};
