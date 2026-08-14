<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('member_inventory_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();

            $table->unique(['member_inventory_id', 'ingredient_id']);
            $table->index('member_inventory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_inventory_ingredients');
    }
};
