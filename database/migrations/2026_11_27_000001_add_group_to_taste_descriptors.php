<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('taste_descriptors', function (Blueprint $table) {
            $table->string('group')->nullable()->after('normalized_name');
        });

        $groups = [
            'bitter' => 'taste',
            'sweet' => 'taste',
            'dry' => 'taste',
            'sour' => 'taste',
            'hoppy' => 'family',
            'fruity' => 'family',
            'spicy' => 'family',
            'smoky' => 'family',
            'citrusy' => 'family',
            'tropical' => 'family',
            'piney' => 'family',
            'herbaceous' => 'family',
            'herbal' => 'family',
            'earthy' => 'family',
            'woody' => 'family',
            'yeasty' => 'family',
            'funky' => 'family',
            'vegetal' => 'family',
            'medicinal' => 'family',
            'malty' => 'flavor',
            'cereal' => 'flavor',
            'grainy' => 'flavor',
            'toasty' => 'flavor',
            'roasty' => 'flavor',
            'nutty' => 'flavor',
            'caramel' => 'flavor',
            'chocolatey' => 'flavor',
            'coffee' => 'flavor',
            'vanilla' => 'flavor',
            'clove' => 'flavor',
            'bubblegum' => 'flavor',
            'peppery' => 'flavor',
            'metallic' => 'flavor',
            'crisp' => 'mouthfeel',
            'smooth' => 'mouthfeel',
            'creamy' => 'mouthfeel',
            'chewy' => 'mouthfeel',
            'fizzy' => 'mouthfeel',
            'warming' => 'mouthfeel',
            'astringent' => 'mouthfeel',
            'buttery' => 'mouthfeel',
            'light body' => 'body',
            'medium body' => 'body',
            'full body' => 'body',
            'clean finish' => 'finish',
            'lingering finish' => 'finish',
            'balanced' => 'profile',
            'simple' => 'profile',
            'complex' => 'profile',
            'refreshing' => 'profile',
        ];

        $rows = DB::table('taste_descriptors')->whereNull('group')->get(['id', 'normalized_name']);
        foreach ($rows as $row) {
            assert(is_int($row->id));
            assert(is_string($row->normalized_name));
            $group = $groups[$row->normalized_name] ?? null;
            if ($group === null) {
                continue;
            }

            DB::table('taste_descriptors')->where('id', $row->id)->update([
                'group' => $group,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taste_descriptors', function (Blueprint $table) {
            $table->dropColumn('group');
        });
    }
};
