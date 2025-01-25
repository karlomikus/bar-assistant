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
        Schema::create('calculators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
            $table->text('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('calculator_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculator_id')->constrained()->onDelete('cascade');
            $table->text('label');
            $table->string('type');
            $table->text('variable_name');
            $table->text('value');
            $table->integer('sort')->default(0);
            $table->text('description')->nullable();
            $table->json('settings')->default('{}');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('calculator_id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('calculator_id');
        });
        Schema::dropIfExists('calculator_blocks');
        Schema::dropIfExists('calculators');
    }
};
