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
        Schema::create('ingredient_review_taste_descriptor', function (Blueprint $table) {
            $table->foreignId('ingredient_review_id')->constrained()->onDelete('cascade');
            $table->foreignId('taste_descriptor_id')->constrained()->onDelete('cascade');

            $table->unique(['ingredient_review_id', 'taste_descriptor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_review_taste_descriptor');
    }
};
