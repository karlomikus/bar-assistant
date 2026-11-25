<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ingredient_taste_descriptor', function (Blueprint $table) {
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->foreignId('taste_descriptor_id')->constrained()->onDelete('cascade');

            $table->unique(['ingredient_id', 'taste_descriptor_id']);
        });

        // Seed ingredient descriptors from existing review descriptors.
        // Descriptors missing from the static taste_descriptors table are removed.
        $links = DB::table('ingredient_review_taste_descriptor')
            ->join('ingredient_reviews', 'ingredient_reviews.id', '=', 'ingredient_review_taste_descriptor.ingredient_review_id')
            ->join('taste_descriptors', 'taste_descriptors.id', '=', 'ingredient_review_taste_descriptor.taste_descriptor_id')
            ->select('ingredient_reviews.ingredient_id', 'ingredient_review_taste_descriptor.taste_descriptor_id')
            ->distinct()
            ->get()
            ->map(fn (object $link): array => [
                'ingredient_id' => (int) $link->ingredient_id,
                'taste_descriptor_id' => (int) $link->taste_descriptor_id,
            ])
            ->values()
            ->all();

        if ($links !== []) {
            DB::table('ingredient_taste_descriptor')->insert($links);
        }

        Schema::drop('ingredient_review_taste_descriptor');
    }

    public function down(): void
    {
        Schema::drop('ingredient_taste_descriptor');
    }
};
