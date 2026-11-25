<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        $links = DB::table('ingredient_review_taste_descriptor')
            ->join('taste_descriptors', 'taste_descriptors.id', '=', 'ingredient_review_taste_descriptor.taste_descriptor_id')
            ->select([
                'ingredient_review_taste_descriptor.ingredient_review_id',
                'taste_descriptors.normalized_name',
            ])
            ->get();

        Schema::drop('ingredient_review_taste_descriptor');
        Schema::drop('taste_descriptors');

        Schema::create('taste_descriptors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->timestamps();
        });

        $now = now();
        DB::table('taste_descriptors')->insert(array_map(
            fn (string $name): array => [
                'name' => $name,
                'normalized_name' => mb_strtolower($name),
                'created_at' => $now,
                'updated_at' => null,
            ],
            $this->descriptorNames(),
        ));

        Schema::create('ingredient_review_taste_descriptor', function (Blueprint $table) {
            $table->foreignId('ingredient_review_id')->constrained()->onDelete('cascade');
            $table->foreignId('taste_descriptor_id')->constrained()->onDelete('cascade');

            $table->unique(['ingredient_review_id', 'taste_descriptor_id']);
        });

        $descriptorIds = DB::table('taste_descriptors')
            ->pluck('id', 'normalized_name');

        $restoredLinks = $links
            ->map(function (object $link) use ($descriptorIds): ?array {
                if (!is_string($link->normalized_name) || !is_numeric($link->ingredient_review_id)) {
                    return null;
                }

                $descriptorId = $descriptorIds->get($link->normalized_name);
                if (!is_numeric($descriptorId)) {
                    return null;
                }

                return [
                    'ingredient_review_id' => (int) $link->ingredient_review_id,
                    'taste_descriptor_id' => (int) $descriptorId,
                ];
            })
            ->filter()
            ->unique(fn (array $link): string => $link['ingredient_review_id'] . ':' . $link['taste_descriptor_id'])
            ->values()
            ->all();

        if ($restoredLinks !== []) {
            DB::table('ingredient_review_taste_descriptor')->insert($restoredLinks);
        }
    }

    public function down(): void
    {
        Schema::drop('ingredient_review_taste_descriptor');
        Schema::drop('taste_descriptors');
    }

    /**
     * @return string[]
     */
    private function descriptorNames(): array
    {
        return [
            'Astringent',
            'Balanced',
            'Bitter',
            'Bubblegum',
            'Buttery',
            'Caramel',
            'Cereal',
            'Chewy',
            'Chocolatey',
            'Citrusy',
            'Clean finish',
            'Clove',
            'Coffee',
            'Complex',
            'Creamy',
            'Crisp',
            'Dry',
            'Earthy',
            'Fizzy',
            'Floral',
            'Fruity',
            'Full body',
            'Funky',
            'Grainy',
            'Herbacious',
            'Herbal',
            'Hoppy',
            'Light body',
            'Lingering finish',
            'Malty',
            'Medicinal',
            'Medium body',
            'Metallic',
            'Nutty',
            'Peppery',
            'Piney',
            'Refreshing',
            'Roasty',
            'Simple',
            'Smoky',
            'Smooth',
            'Sour',
            'Spicy',
            'Sweet',
            'Toasty',
            'Tropical',
            'Vanilla',
            'Vegetal',
            'Warming',
            'Woody',
            'Yeasty',
        ];
    }
};
