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
        Schema::create('cocktail_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cocktail_id')->constrained()->onDelete('cascade');
            $table->foreignId('bar_membership_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->timestamps();

            $table->unique(['bar_membership_id', 'cocktail_id']);
            $table->index(['cocktail_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cocktail_reviews');
    }
};
