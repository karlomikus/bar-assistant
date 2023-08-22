<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('users', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->string('email')->unique();
        //     $table->timestamp('email_verified_at')->nullable();
        //     $table->string('password');
        //     $table->boolean('is_admin')->default(false);
        //     $table->string('search_api_key')->nullable();
        //     $table->rememberToken();
        //     $table->timestamps();
        // });

        // Schema::create('password_resets', function (Blueprint $table) {
        //     $table->string('email')->index();
        //     $table->string('token');
        //     $table->timestamp('created_at')->nullable();
        // });

        // Schema::create('failed_jobs', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('uuid')->unique();
        //     $table->text('connection');
        //     $table->text('queue');
        //     $table->longText('payload');
        //     $table->longText('exception');
        //     $table->timestamp('failed_at')->useCurrent();
        // });

        // Schema::create('personal_access_tokens', function (Blueprint $table) {
        //     $table->id();
        //     $table->morphs('tokenable');
        //     $table->string('name');
        //     $table->string('token', 64)->unique();
        //     $table->text('abilities')->nullable();
        //     $table->timestamp('last_used_at')->nullable();
        //     $table->timestamp('expires_at')->nullable();
        //     $table->timestamps();
        // });

        // Schema::create('bar_types', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        // });

        // DB::table('bar_types')->insert([
        //     ['id' => 1, 'name' => 'Normal'],
        //     ['id' => 2, 'name' => 'Premium'],
        // ]);

        // Schema::create('bars', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->string('subtitle')->nullable();
        //     $table->string('description')->nullable();
        //     $table->string('search_driver_api_key')->nullable();
        //     $table->foreignId('bar_type_id')->default(1)->constrained()->onDelete('restrict');
        //     $table->foreignId('user_id')->constrained()->onDelete('restrict');
        //     $table->string('invite_code')->unique()->nullable();
        //     $table->boolean('active')->default(false);
        //     $table->timestamps();
        // });

        // Schema::create('glasses', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        //     $table->string('name');
        //     $table->text('description')->nullable();
        //     $table->timestamps();
        // });

        // Schema::create('cocktail_methods', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->text('description')->nullable();
        //     $table->tinyInteger('dilution_percentage');
        // });

        // DB::table('cocktail_methods')->insert([
        //     ['name' => 'Shake', 'dilution_percentage' => 25],
        //     ['name' => 'Stir', 'dilution_percentage' => 20],
        //     ['name' => 'Build', 'dilution_percentage' => 10],
        //     ['name' => 'Blend', 'dilution_percentage' => 25],
        //     ['name' => 'Muddle', 'dilution_percentage' => 10],
        //     ['name' => 'Layer', 'dilution_percentage' => 0],
        // ]);

        // Schema::create('ingredient_categories', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('bar_id')->constrained()->onDelete('cascade');
        //     $table->string('name');
        //     $table->text('description')->nullable();
        //     $table->timestamps();
        // });

        // Schema::create('ingredients', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('slug')->unique();
        //     $table->string('name');
        //     $table->decimal('strength')->default(0.0);
        //     $table->string('description')->nullable();
        //     $table->text('origin')->nullable();
        //     $table->text('history')->nullable();
        //     $table->string('color')->nullable();
        //     $table->foreignId('ingredient_category_id')->constrained();
        //     $table->foreignId('parent_ingredient_id')->nullable()->constrained('ingredients')->onDelete('cascade');
        //     $table->text('aliases')->nullable();
        //     $table->foreignId('user_id')->constrained('users');
        //     $table->timestamps();
        // });

        // Schema::create('cocktails', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('slug')->unique();
        //     $table->string('name');
        //     $table->text('instructions');
        //     $table->text('description')->nullable();
        //     $table->string('source')->nullable();
        //     $table->text('garnish')->nullable();
        //     $table->foreignId('user_id')->constrained();
        //     $table->foreignId('glass_id')->nullable()->constrained();
        //     $table->foreignId('cocktail_method_id')->nullable()->constrained('cocktail_methods');
        //     $table->ulid('public_id')->nullable();
        //     $table->dateTime('public_at')->nullable();
        //     $table->dateTime('public_expires_at')->nullable();
        //     $table->decimal('abv')->nullable();
        //     $table->index('abv', 'cocktails_abv_index');
        //     $table->timestamps();
        // });

        // Schema::create('cocktail_ingredients', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('cocktail_id')->constrained()->onDelete('cascade');
        //     $table->decimal('amount');
        //     $table->string('units');
        //     $table->integer('sort')->default(0);
        //     $table->boolean('optional')->default(false);
        // });

        // Schema::create('cocktail_favorites', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('user_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('cocktail_id')->unique()->constrained()->onDelete('cascade');
        //     $table->timestamps();
        // });

        // Schema::create('tags', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        // });

        // Schema::create('cocktail_tag', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('tag_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('cocktail_id')->constrained()->onDelete('cascade');
        // });

        // Schema::create('user_ingredients', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('user_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
        //     $table->unique(['user_id', 'ingredient_id']);
        // });

        // Schema::create('images', function (Blueprint $table) {
        //     $table->id();
        //     $table->nullableMorphs('imageable');
        //     $table->string('file_path');
        //     $table->string('file_extension');
        //     $table->string('copyright')->nullable();
        //     $table->foreignId('user_id')->constrained('users');
        //     $table->integer('sort')->default(1);
        //     $table->string('placeholder_hash')->nullable();
        //     $table->timestamps();
        // });

        // Schema::create('user_shopping_lists', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('user_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
        //     $table->timestamps();

        //     $table->unique(['user_id', 'ingredient_id']);
        // });

        // Schema::create('cocktail_ingredient_substitutes', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('cocktail_ingredient_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
        //     $table->timestamps();
        // });

        // Schema::create('ratings', function (Blueprint $table) {
        //     $table->id();
        //     $table->morphs('rateable');
        //     $table->foreignId('user_id')->constrained()->onDelete('cascade');
        //     $table->smallInteger('rating');
        //     $table->timestamps();
        //     $table->unique(['user_id', 'rateable_id', 'rateable_type']);
        // });

        // Schema::create('notes', function (Blueprint $table) {
        //     $table->id();
        //     $table->morphs('noteable');
        //     $table->foreignId('user_id')->constrained()->onDelete('cascade');
        //     $table->string('note');
        //     $table->timestamps();
        // });

        // Schema::create('collections', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->string('description')->nullable();
        //     $table->foreignId('user_id')->constrained()->onDelete('cascade');
        //     $table->timestamps();
        // });

        // Schema::create('collections_cocktails', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('cocktail_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('collection_id')->constrained()->onDelete('cascade');

        //     $table->unique(['cocktail_id', 'collection_id']);
        // });

        // Schema::create('utensils', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->text('description')->nullable();
        //     $table->timestamps();
        // });

        // Schema::create('cocktail_utensil', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('cocktail_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('utensil_id')->constrained()->onDelete('cascade');
        // });

        // DB::table('utensils')->insert([
        //     ['name' => 'Mixing glass', 'description' => 'A glass with a heavy base that doesn\'t tip over when stirring.'],
        //     ['name' => 'Shaker', 'description' => 'A recipient in 2 parts to shake cocktails vigorously.'],
        //     ['name' => 'Bar spoon', 'description' => 'A long and heavy spiraled spoon used to stir or layer cocktails.'],
        //     ['name' => 'Julep Strainer', 'description' => 'A style of strainer used when using a mixing glass to strain the ice out.'],
        //     ['name' => 'Hawthorne Strainer', 'description' => 'A style of strainer used when using a shaker to strain the ice out.'],
        //     ['name' => 'Mesh Strainer', 'description' => 'A simple mesh strainer used to double strain cocktails in order to avoid any ice in the final drink, or to avoid pulp when juicing fruits.'],
        //     ['name' => 'Atomizer', 'description' => 'Refillable glass spray bottle to spray and mist very small amounts of aromatics. Used for absinthe rinses, and bitter sprays.'],
        //     ['name' => 'Muddler', 'description' => 'Essential tool to crush fruits, berries and herbs and extract the juice out of them.'],
        //     ['name' => 'Jigger', 'description' => 'Small cup used to quickly measure volumes in the bar.'],
        //     ['name' => 'Zester', 'description' => 'Rasp used to zest fruits, nuts, or even chocolate for garnishes.'],
        //     ['name' => 'Channel knife', 'description' => 'Knife designed to make long and thin citrus peels.'],
        //     ['name' => 'Y Peeler', 'description' => 'Kitchen tool designed to peel fruits and vegetables. In the bar, used for large peels to extract the oils from.'],
        //     ['name' => 'Bar knife', 'description' => 'A small sharp knife to peel and cut fruits.'],
        //     ['name' => 'Ice carving knife', 'description' => 'A knife with a significantly tougher spine designed to handle ice carving.'],
        //     ['name' => 'Ice chipper', 'description' => 'A three-pronged tool to chip away and break ice.'],
        //     ['name' => 'Ice pick', 'description' => 'A pick to break and chip away at ice.'],
        //     ['name' => 'Cocktail smoker', 'description' => 'A device used to add smokey flavor to cocktails by burning different wood escences.'],
        //     ['name' => 'Juicer', 'description' => 'Extract juice from citrus fruits.'],
        //     ['name' => 'Straight tongs', 'description' => 'Small precision tongs to place garnishes.'],
        //     ['name' => 'Ice tongs', 'description' => 'Tongs made to grab ice cubes.'],
        // ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
