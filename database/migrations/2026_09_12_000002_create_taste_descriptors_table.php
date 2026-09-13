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
        Schema::create('taste_descriptors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('normalized_name');
            $table->timestamps();

            $table->unique(['bar_id', 'normalized_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taste_descriptors');
    }
};
