<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
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

        // Misc
        $ice = Ingredient::create(['name' => 'Ice', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => 'Ice cubes.']);
        $wPeachPuree = Ingredient::create(['name' => 'White Peach Puree', 'ingredient_category_id' => $misc->id, 'strength' => 0.0, 'description' => 'TODO']);

        // Bitters
        Ingredient::create(['name' => 'Orange bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 28.0, 'image' => '/ingredients/bitters/angostura_orange.png', 'description' => 'Orange bitters is a form of bitters, a cocktail flavoring made from such ingredients as the peels of Seville oranges, cardamom, caraway seed, coriander, anise, and burnt sugar in an alcohol base.']);
        $angostura = Ingredient::create(['name' => 'Angostura aromatic bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 44.7, 'image' => '/ingredients/bitters/angostura_aromatic.png', 'description' => 'Angostura bitters is a concentrated bitters (herbal alcoholic preparation) based on gentian, herbs, and spices, by House of Angostura in Trinidad and Tobago.']);
        Ingredient::create(['name' => 'Peach bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 35.0, 'image' => '/ingredients/bitters/peach_bitters.jpg', 'description' => 'Peach bitters flavored with peaches and herbs.']);
        Ingredient::create(['name' => 'Angostura cocoa bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 38.0, 'image' => '/ingredients/bitters/angostura_cocoa.png', 'description' => 'Top notes of rich bitter, floral, nutty cocoa with a bold infusion of aromatic botanicals provide endless possibilities to remix classic cocktails.']);

        // Liqueurs
        $campari = Ingredient::create(['name' => 'Campari', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'Aperol', 'ingredient_category_id' => $liqueurs->id, 'strength' => 11.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'Kahlua coffee liqueur', 'ingredient_category_id' => $liqueurs->id, 'strength' => 20.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'Amaretto', 'ingredient_category_id' => $liqueurs->id, 'strength' => 24.0, 'description' => 'Sweet almond-flavored liqueur']);
        Ingredient::create(['name' => 'Dark Crème de Cacao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'White Crème de Cacao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'Cointreau', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'Grand Marnier', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'Suze', 'ingredient_category_id' => $liqueurs->id, 'strength' => 15.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'Triple Sec', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => 'TODO']);

        // Juices
        $lemJuice = Ingredient::create(['name' => 'Lemon juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed lemon juice.']);
        $limeJuice = Ingredient::create(['name' => 'Lime juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed lime juice.']);
        $oranJuice = Ingredient::create(['name' => 'Orange juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed orange juice.']);
        $grfJuice = Ingredient::create(['name' => 'Grapefruit juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed grapefruit juice.']);
        $cranJuice = Ingredient::create(['name' => 'Cranberry juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Cranberry juice.']);
        $tmtJuice = Ingredient::create(['name' => 'Tomato juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Tomato juice.']);
        $pineaJuice = Ingredient::create(['name' => 'Pineapple juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'TODO']);

        // Beverages
        Ingredient::create(['name' => 'Cola', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => 'Coca cola.']);
        Ingredient::create(['name' => 'Club soda', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'Tonic', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => 'TODO']);
        Ingredient::create(['name' => '7up', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'Ginger beer', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'Water', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => 'TODO']);
        Ingredient::create(['name' => 'Coffee', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'description' => 'TODO']);

        // Spirits
        $vodka = Ingredient::create(['name' => 'Vodka', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'Vodka desc.']);
        $whiskey = Ingredient::create(['name' => 'Whiskey', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'Whiskey desc.']);
        $bourbon = Ingredient::create(['name' => 'Bourbon', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'Bourbon desc.']);
        $ryeWhiskey = Ingredient::create(['name' => 'Rye whiskey', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'TODO']);
        $scotchWhiskey = Ingredient::create(['name' => 'Scotch whiskey', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'TODO']);
        $irishWhiskey = Ingredient::create(['name' => 'Irish whiskey', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'TODO']);
        $gin = Ingredient::create(['name' => 'Gin', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'TODO']);
        $cognac = Ingredient::create(['name' => 'Cognac', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'TODO']);
        $brandy = Ingredient::create(['name' => 'Brandy', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'TODO']);
        $calvados = Ingredient::create(['name' => 'Calvados', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'TODO']);
        $tequila = Ingredient::create(['name' => 'Tequila', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'TODO']);
        $mezcal = Ingredient::create(['name' => 'Mezcal', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'TODO']);
        $absinthe = Ingredient::create(['name' => 'Absinthe', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'TODO']);
        $cachaca = Ingredient::create(['name' => 'Cachaca', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'description' => 'TODO']);

        // Syrups
        $simpleSyrup = Ingredient::create(['name' => 'Simple syrup', 'ingredient_category_id' => $syrups->id, 'strength' => 0.0, 'description' => 'Equal parts water and sugar.']);
        $orgeatSyrup = Ingredient::create(['name' => 'Orgeat syrup', 'ingredient_category_id' => $syrups->id, 'strength' => 0.0, 'description' => 'Orgeat syrup is a sweet syrup made from almonds, sugar, and rose water or orange flower water.']);
        $honeySyrup = Ingredient::create(['name' => 'Honey syrup', 'ingredient_category_id' => $syrups->id, 'strength' => 0.0, 'description' => 'TODO']);

        // Wines
        $sweetVermouth = Ingredient::create(['name' => 'Sweet Vermouth', 'ingredient_category_id' => $wines->id, 'strength' => 18.0, 'description' => 'TODO']);
        $dryVermouth = Ingredient::create(['name' => 'Dry Vermouth', 'ingredient_category_id' => $wines->id, 'strength' => 18.0, 'description' => 'TODO']);
        $whiteWine = Ingredient::create(['name' => 'White wine', 'ingredient_category_id' => $wines->id, 'strength' => 11.0, 'description' => 'TODO']);
        $redWine = Ingredient::create(['name' => 'Red wine', 'ingredient_category_id' => $wines->id, 'strength' => 11.0, 'description' => 'TODO']);
        $prosecco = Ingredient::create(['name' => 'Prosecco', 'ingredient_category_id' => $wines->id, 'strength' => 11.0, 'description' => 'TODO']);
        $champagne = Ingredient::create(['name' => 'Champagne', 'ingredient_category_id' => $wines->id, 'strength' => 12.0, 'description' => 'TODO']);

        // Cocktails
        $whSour = Cocktail::create(['name' => 'Whiskey Sour', 'instructions' => '1. Do this 2. Do that 3. Share 4. Pour', 'image' => 'whiskey-sour.png']);
        $whSour->ingredients()->createMany([
            ['ingredient_id' => $bourbon->id, 'amount' => 45, 'units' => 'ml'],
            ['ingredient_id' => $lemJuice->id, 'amount' => 30, 'units' => 'ml'],
            ['ingredient_id' => $simpleSyrup->id, 'amount' => 15, 'units' => 'ml'],
        ]);

        $garibaldi = Cocktail::create(['name' => 'Garibaldi', 'instructions' => '1. Do this 2. Do that 3. Share 4. Pour', 'image' => 'garibaldi.jpg']);
        $garibaldi->ingredients()->createMany([
            ['ingredient_id' => $campari->id, 'amount' => 45, 'units' => 'ml'],
            ['ingredient_id' => $oranJuice->id, 'amount' => 120, 'units' => 'ml'],
            ['ingredient_id' => $ice->id, 'amount' => 1, 'units' => 'cubes'],
        ]);

        $garibaldi = Cocktail::create(['name' => 'Negroni', 'instructions' => '1. Do this 2. Do that 3. Share 4. Pour', 'image' => 'negorni.jpg']);
        $garibaldi->ingredients()->createMany([
            ['ingredient_id' => $gin->id, 'amount' => 30, 'units' => 'ml'],
            ['ingredient_id' => $campari->id, 'amount' => 30, 'units' => 'ml'],
            ['ingredient_id' => $sweetVermouth->id, 'amount' => 30, 'units' => 'ml'],
            ['ingredient_id' => $ice->id, 'amount' => 1, 'units' => 'cubes'],
        ]);

        $manhattan = Cocktail::create(['name' => 'Manhattan', 'instructions' => '1. Do this 2. Do that 3. Share 4. Pour', 'image' => 'manhattan.jpg']);
        $manhattan->ingredients()->createMany([
            ['ingredient_id' => $whiskey->id, 'amount' => 50, 'units' => 'ml'],
            ['ingredient_id' => $sweetVermouth->id, 'amount' => 20, 'units' => 'ml'],
            ['ingredient_id' => $angostura->id, 'amount' => 1, 'units' => 'dash'],
            ['ingredient_id' => $ice->id, 'amount' => 1, 'units' => 'cubes'],
        ]);

        $oldFashioned = Cocktail::create(['name' => 'Old Fashioned', 'instructions' => '1. Do this 2. Do that 3. Share 4. Pour', 'image' => 'old-fashioned.png']);
        $oldFashioned->ingredients()->createMany([
            ['ingredient_id' => $whiskey->id, 'amount' => 45, 'units' => 'ml'],
            ['ingredient_id' => $angostura->id, 'amount' => 2, 'units' => 'dashes'],
            ['ingredient_id' => $simpleSyrup->id, 'amount' => 15, 'units' => 'ml'],
        ]);
    }
}
