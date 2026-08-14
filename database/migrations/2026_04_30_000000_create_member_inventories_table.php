<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('member_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bar_membership_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            $table->unique(['bar_membership_id', 'name']);
            $table->index('bar_membership_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_inventories');
    }
};
