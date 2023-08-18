<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bar_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        DB::table('bar_types')->insert([
            ['id' => 1, 'name' => 'Normal'],
            ['id' => 2, 'name' => 'Premium'],
        ]);

        Schema::create('bars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subtitle')->nullable();
            $table->string('description')->nullable();
            $table->string('search_driver_api_key')->nullable();
            $table->foreignId('bar_type_id')->default(1)->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        // User can have only one role
        // Roles have permission levels in asc/desc order
        // Hopefully this design wont annoy me in the future :^)
        DB::table('user_roles')->insert([
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Moderator'],
            ['id' => 3, 'name' => 'General'],
            ['id' => 4, 'name' => 'Guest'],
        ]);

        Schema::create('bar_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bar_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_role_id')->constrained()->onDelete('restrict');
            $table->timestamps();

            $table->unique(['bar_id', 'user_id', 'user_role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bar_memberships');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('bars');
        Schema::dropIfExists('bar_types');
    }
};
