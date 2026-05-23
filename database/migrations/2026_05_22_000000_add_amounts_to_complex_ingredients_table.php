<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('complex_ingredients', function (Blueprint $table): void {
            $table->decimal('amount', 12, 3)->default(0)->after('ingredient_id');
            $table->decimal('amount_max', 12, 3)->nullable()->after('amount');
            $table->string('units', 20)->default('unit')->after('amount_max');
            $table->text('note')->nullable()->after('units');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complex_ingredients', function (Blueprint $table): void {
            $table->dropColumn(['amount', 'amount_max', 'units', 'note']);
        });
    }
};
