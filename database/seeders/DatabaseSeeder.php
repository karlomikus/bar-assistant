<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\IngredientCategory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();

        DB::table('users')->insert([
            [
                'name' => 'System',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'email' => 'system',
                'email_verified_at' => null,
                'remember_token' => null,
            ],
            [
                'name' => 'Bartender',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'email' => 'admin@example.com',
                'email_verified_at' => now(),
                'remember_token' => Str::random(10)
            ]
        ]);

        DB::table('glasses')->insert([
            ['name' => 'Cocktail glass', 'description' => 'A cocktail glass is a stemmed glass with an inverted cone bowl, mainly used to serve straight-up cocktails. The term cocktail glass is often used interchangeably with martini glass, despite their differing slightly. A standard cocktail glass contains 90 to 300 millilitres.'],
            ['name' => 'Lowball glass', 'description' => 'The old fashioned glass, otherwise known as the rocks glass and lowball glass (or simply lowball), is a short tumbler used for serving spirits, such as whisky, neat or with ice cubes ("on the rocks"). Old fashioned glasses usually contain 180–300 ml.'],
            ['name' => 'Highball glass', 'description' => 'A highball glass is a glass tumbler that can contain 240 to 350 millilitres (8 to 12 US fl oz).'],
            ['name' => 'Shot glass', 'description' => 'A shot glass is a glass originally designed to hold or measure spirits or liquor, which is either imbibed straight from the glass ("a shot") or poured into a cocktail ("a drink").'],
            ['name' => 'Coupe glass', 'description' => 'The champagne coupe is a shallow, broad-bowled saucer shaped stemmed glass generally capable of containing 180 to 240 ml (6.1 to 8.1 US fl oz) of liquid.'],
            ['name' => 'Margarita glass', 'description' => 'A variant of the classic champagne coupe.'],
            ['name' => 'Wine glass', 'description' => 'A wine glass is a type of glass that is used to drink and taste wine. Most wine glasses are stemware (goblets), i.e., they are composed of three parts: the bowl, stem, and foot.'],
            ['name' => 'Champagne glass', 'description' => 'A champagne glass is stemware designed for champagne and other sparkling wines.'],
            ['name' => 'Hurricane glass', 'description' => 'A hurricane glass is a form of drinking glass which typically will contain 20 US fluid ounces.'],
        ]);

        $spirits = IngredientCategory::create(['name' => 'Spirits']);
        $liqueurs = IngredientCategory::create(['name' => 'Liqueurs']);
        $juices = IngredientCategory::create(['name' => 'Juices']);
        $fruits = IngredientCategory::create(['name' => 'Fruits']);
        $syrups = IngredientCategory::create(['name' => 'Syrups']);
        $wines = IngredientCategory::create(['name' => 'Wines']);
        $bitters = IngredientCategory::create(['name' => 'Bitters']);
        $beverages = IngredientCategory::create(['name' => 'Beverages']);
        $misc = IngredientCategory::create(['name' => 'Misc.']);

        // Fruits
        Ingredient::create(['name' => 'Lime', 'ingredient_category_id' => $fruits->id, 'strength' => 0.0, 'description' => $faker->sentence()]);

        // Misc
        Ingredient::create(['name' => 'White Peach Puree', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Cream', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Salt', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Pepper', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Tabasco', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Worcestershire Sauce', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Sugar', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Egg White', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Egg Yolk', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Mint', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Ginger', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Coconut Cream', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Vanilla Extract', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Chili Pepper', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => $faker->sentence()]);

        // Bitters
        Ingredient::create(['name' => 'Orange bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 28.0, 'image' => '/ingredients/bitters/angostura_orange.png', 'description' => 'Orange bitters is a form of bitters, a cocktail flavoring made from such ingredients as the peels of Seville oranges, cardamom, caraway seed, coriander, anise, and burnt sugar in an alcohol base.']);
        Ingredient::create(['name' => 'Angostura aromatic bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 44.7, 'image' => '/ingredients/bitters/angostura_aromatic.png', 'description' => 'Angostura bitters is a concentrated bitters (herbal alcoholic preparation) based on gentian, herbs, and spices, by House of Angostura in Trinidad and Tobago.']);
        Ingredient::create(['name' => 'Peach bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 35.0, 'image' => '/ingredients/bitters/peach_bitters.jpg', 'description' => 'Peach bitters flavored with peaches and herbs.']);
        Ingredient::create(['name' => 'Angostura cocoa bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 38.0, 'image' => '/ingredients/bitters/angostura_cocoa.png', 'description' => 'Top notes of rich bitter, floral, nutty cocoa with a bold infusion of aromatic botanicals provide endless possibilities to remix classic cocktails.']);
        Ingredient::create(['name' => 'Fernet Branca', 'ingredient_category_id' => $bitters->id, 'strength' => 39.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Peychauds Bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 35.0, 'description' => $faker->sentence()]);

        // Liqueurs
        Ingredient::create(['name' => 'Campari', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Aperol', 'ingredient_category_id' => $liqueurs->id, 'strength' => 11.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Kahlua coffee liqueur', 'ingredient_category_id' => $liqueurs->id, 'strength' => 20.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Amaretto', 'ingredient_category_id' => $liqueurs->id, 'strength' => 24.0, 'description' => 'Sweet almond-flavored liqueur']);
        Ingredient::create(['name' => 'Dark Crème de Cacao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'White Crème de Cacao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Menthe Crème de Cacao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Crème de cassis', 'ingredient_category_id' => $liqueurs->id, 'strength' => 15.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Cointreau', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Grand Marnier', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Suze', 'ingredient_category_id' => $liqueurs->id, 'strength' => 15.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Triple Sec', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Maraschino Luxardo', 'ingredient_category_id' => $liqueurs->id, 'strength' => 32.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Crème de Violette', 'ingredient_category_id' => $liqueurs->id, 'strength' => 16.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Galliano', 'ingredient_category_id' => $liqueurs->id, 'strength' => 42.3, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Crème de mûre (blackberry liqueur)', 'ingredient_category_id' => $liqueurs->id, 'strength' => 42.3, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Orange Curaçao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Blue Curaçao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Raspberry Liqueur', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Falernum', 'ingredient_category_id' => $liqueurs->id, 'strength' => 11.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Green Chartreuse', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Yellow Chartreuse', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Maraschino Liqueur', 'ingredient_category_id' => $liqueurs->id, 'strength' => 32.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Amaro Nonino', 'ingredient_category_id' => $liqueurs->id, 'strength' => 35.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Drambuie', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Bénédictine', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Pernod', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Cherry liqueur', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => $faker->sentence()]);

        // Juices
        Ingredient::create(['name' => 'Lemon juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed lemon juice.']);
        Ingredient::create(['name' => 'Lime juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed lime juice.']);
        Ingredient::create(['name' => 'Orange juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed orange juice.']);
        Ingredient::create(['name' => 'Grapefruit juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed grapefruit juice.']);
        Ingredient::create(['name' => 'Cranberry juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Cranberry juice.']);
        Ingredient::create(['name' => 'Tomato juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Tomato juice.']);
        Ingredient::create(['name' => 'Pineapple juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Elderflower Cordial', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Chamomile cordial', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => $faker->sentence()]);

        // Beverages
        Ingredient::create(['name' => 'Cola', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => 'Coca cola.']);
        Ingredient::create(['name' => 'Club soda', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Tonic', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => '7up', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Ginger beer', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Water', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Espresso', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Coffee', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Orange Flower Water', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => $faker->sentence()]);

        // Spirits
        Ingredient::create(['name' => 'Vodka', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'Vodka desc.']);
        Ingredient::create(['name' => 'Whiskey', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'Whiskey desc.']);
        Ingredient::create(['name' => 'Bourbon Whiskey', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'Bourbon desc.']);
        Ingredient::create(['name' => 'Rye whiskey', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Scotch whiskey', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Islay Scotch', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Irish whiskey', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Gin', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Cognac', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Brandy', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Apricot Brandy', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Calvados', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Tequila', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Mezcal', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Absinthe', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Gold Rum', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'White Rum', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Demerara Rum', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Dark Rum', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Cachaça', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Pisco', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Jamaican rum', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Peach Schnapps', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => $faker->sentence()]);
        Ingredient::create(['name' => 'Grappa', 'ingredient_category_id' => $spirits->id, 'strength' => 50.0, 'description' => $faker->sentence()]);

        // Syrups
        Ingredient::create(['name' => 'Simple Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Equal parts water and sugar.', 'color' => '#e6dfcc']);
        Ingredient::create(['name' => 'Orgeat Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Sweet syrup made from almonds, sugar, and rose water or orange flower water.', 'color' => '#d9ca9f']);
        Ingredient::create(['name' => 'Honey Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Syrup made from dissolving honey in water.', 'color' => '#f2a900']);
        Ingredient::create(['name' => 'Raspberry Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Fruit syrup made from raspberries.', 'color' => '#b71f23']);
        Ingredient::create(['name' => 'Grenadine Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Fruit syrup made from grenadine.', 'color' => '#bb0014']);
        Ingredient::create(['name' => 'Agave Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Syrup made from agave.', 'color' => '#deca3f']);
        Ingredient::create(['name' => 'Donn\'s Mix', 'ingredient_category_id' => $syrups->id, 'description' => '2 parts fresh yellow grapefruit and 1 part cinnamon syrup', 'color' => '#c6972c']);

        // Wines
        Ingredient::create(['name' => 'Sweet Vermouth', 'ingredient_category_id' => $wines->id, 'strength' => 18.0, 'description' => 'Aromatized fortified wine.']);
        Ingredient::create(['name' => 'Dry Vermouth', 'ingredient_category_id' => $wines->id, 'strength' => 18.0, 'description' => 'Aromatized fortified wine.']);
        Ingredient::create(['name' => 'White wine', 'ingredient_category_id' => $wines->id, 'strength' => 11.0, 'description' => 'Wine is an alcoholic drink typically made from fermented grapes.']);
        Ingredient::create(['name' => 'Red wine', 'ingredient_category_id' => $wines->id, 'strength' => 11.0, 'description' => 'Red wine is a type of wine made from dark-colored grape varieties.']);
        Ingredient::create(['name' => 'Prosecco', 'ingredient_category_id' => $wines->id, 'strength' => 11.0, 'description' => 'Sparkling wine made from Prosecco grapes.']);
        Ingredient::create(['name' => 'Champagne', 'ingredient_category_id' => $wines->id, 'strength' => 12.0, 'description' => 'Sparkling wine.']);
        Ingredient::create(['name' => 'Lillet Blanc', 'ingredient_category_id' => $wines->id, 'strength' => 17.0, 'description' => 'Aromatized sweet wine.']);
    }
}
