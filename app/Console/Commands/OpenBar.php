<?php

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Tag;
use Illuminate\Console\Command;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\SearchActions;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Hash;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Models\IngredientCategory;

class OpenBar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:open {email=admin@example.com} {pass=password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run this command to initate your BA instance for the first time';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Model::unguard();

        $this->info('Checking connection to your search server [' . config('scout.meilisearch.host') . ']...');

        try {
            $this->info('Search server: ' . SearchActions::checkHealth()['status']);
        } catch (Throwable $e) {
            $this->error('Unable to connect to search server!');

            throw $e;
        }

        DB::table('users')->insert([
            [
                'name' => 'BAR ASSISTANT BOT',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'email' => 'bot@my-bar.localhost',
                'email_verified_at' => null,
                'remember_token' => null,
                'search_api_key' => null,
            ],
            [
                'name' => 'Bartender',
                'password' => Hash::make($this->argument('pass')),
                'email' => $this->argument('email'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'search_api_key' => SearchActions::getPublicApiKey() // TODO: Check if already exists in ENV
            ]
        ]);

        // Flush site search index in case anything is already there
        SearchActions::flushSearchIndex();

        // Also flush model indexes
        Artisan::call('scout:flush', ['model' => "Kami\Cocktail\Models\Cocktail"]);
        Artisan::call('scout:flush', ['model' => "Kami\Cocktail\Models\Ingredient"]);

        DB::table('glasses')->insert([
            ['name' => 'Cocktail', 'description' => 'A cocktail glass is a stemmed glass with an inverted cone bowl, mainly used to serve straight-up cocktails. The term cocktail glass is often used interchangeably with martini glass, despite their differing slightly. A standard cocktail glass contains 90 to 300 millilitres.'],
            ['name' => 'Lowball', 'description' => 'The old fashioned glass, otherwise known as the rocks glass and lowball glass (or simply lowball), is a short tumbler used for serving spirits, such as whisky, neat or with ice cubes ("on the rocks"). Old fashioned glasses usually contain 180–300 ml.'],
            ['name' => 'Highball', 'description' => 'A highball glass is a glass tumbler that can contain 240 to 350 millilitres (8 to 12 US fl oz).'],
            ['name' => 'Shot', 'description' => 'A shot glass is a glass originally designed to hold or measure spirits or liquor, which is either imbibed straight from the glass ("a shot") or poured into a cocktail ("a drink").'],
            ['name' => 'Coupe', 'description' => 'The champagne coupe is a shallow, broad-bowled saucer shaped stemmed glass generally capable of containing 180 to 240 ml (6.1 to 8.1 US fl oz) of liquid.'],
            ['name' => 'Margarita', 'description' => 'A variant of the classic champagne coupe.'],
            ['name' => 'Wine', 'description' => 'A wine glass is a type of glass that is used to drink and taste wine. Most wine glasses are stemware (goblets), i.e., they are composed of three parts: the bowl, stem, and foot.'],
            ['name' => 'Champagne', 'description' => 'A champagne glass is stemware designed for champagne and other sparkling wines.'],
            ['name' => 'Hurricane', 'description' => 'A hurricane glass is a form of drinking glass which typically will contain 20 US fluid ounces.'],
            ['name' => 'Nick and Nora', 'description' => 'A Nick & Nora glass is a stemmed glass with an inverted bowl, mainly used to serve straight-up cocktails. The glass is similar to a cocktail glass or martini glass.'],
            ['name' => 'Fizzio', 'description' => 'Wide flat bowl on a stem.'],
            ['name' => 'Sour', 'description' => 'Tulip shaped with a fatter stem.'],
            ['name' => 'Julep', 'description' => 'Metal bucket shaped glass.'],
            ['name' => 'Absinthe', 'description' => 'Absinthe glasses have a reservoir in the stem to measure the correct amount of Absinthe for one serving.'],
            ['name' => 'Glass mug', 'description' => 'A mug made of glass.'],
            ['name' => 'Copper mug', 'description' => 'A mug made of copper.'],
            ['name' => 'Tiki', 'description' => 'The term "tiki mug" is a blanket term for the sculptural drinkware even though they vary in size and most do not contain handles.'],
        ]);

        $uncategorized = IngredientCategory::create(['id' => 1, 'name' => 'Uncategorized']);
        $spirits = IngredientCategory::create(['name' => 'Spirits', 'description' => 'Alcoholic drinks produced by distillation of grains, fruits, vegetables, or sugar, that have already gone through alcoholic fermentation.']);
        $liqueurs = IngredientCategory::create(['name' => 'Liqueurs', 'description' => 'Alcoholic drinks composed of spirits (often rectified spirit) and additional flavorings such as sugar, fruits, herbs, and spices.']);
        $juices = IngredientCategory::create(['name' => 'Juices', 'description' => 'Drinks made from the extraction or pressing of the natural liquid contained in fruit and vegetables.']);
        $fruits = IngredientCategory::create(['name' => 'Fruits and vegetables']);
        $syrups = IngredientCategory::create(['name' => 'Syrups', 'description' => 'Condiment that is a thick, viscous liquid consisting primarily of a solution of sugar in water, containing a large amount of dissolved sugars but showing little tendency to deposit crystals.']);
        $wines = IngredientCategory::create(['name' => 'Wines']);
        $bitters = IngredientCategory::create(['name' => 'Bitters']);
        $beverages = IngredientCategory::create(['name' => 'Beverages']);

        $this->info('Filling your bar with ingredients...');

        // Fruits
        Ingredient::create(['name' => 'Lime', 'ingredient_category_id' => $fruits->id, 'strength' => 0.0, 'description' => 'Lime fruit', 'user_id' => 1]);
        Ingredient::create(['name' => 'Lemon', 'ingredient_category_id' => $fruits->id, 'strength' => 0.0, 'description' => 'Lemon fruit', 'user_id' => 1]);
        Ingredient::create(['name' => 'Orange', 'ingredient_category_id' => $fruits->id, 'strength' => 0.0, 'description' => 'Orange fruit', 'user_id' => 1]);
        Ingredient::create(['name' => 'Pineapple', 'ingredient_category_id' => $fruits->id, 'strength' => 0.0, 'description' => 'Pineapple fruit', 'user_id' => 1]);
        Ingredient::create(['name' => 'Apple', 'ingredient_category_id' => $fruits->id, 'strength' => 0.0, 'description' => 'Apple fruit', 'user_id' => 1]);
        Ingredient::create(['name' => 'Peach', 'ingredient_category_id' => $fruits->id, 'strength' => 0.0, 'description' => 'Peach fruit', 'user_id' => 1]);
        Ingredient::create(['name' => 'Mint', 'ingredient_category_id' => $fruits->id, 'strength' => 0.0, 'description' => 'Mint/mentha leaves.', 'user_id' => 1]);
        Ingredient::create(['name' => 'Ginger', 'ingredient_category_id' => $fruits->id, 'strength' => 0.0, 'description' => 'Ginger root used as a spice', 'user_id' => 1]);
        Ingredient::create(['name' => 'Chilli Pepper', 'ingredient_category_id' => $fruits->id, 'strength' => 0.0, 'description' => 'Hot pepper', 'user_id' => 1]);

        // Misc
        Ingredient::create(['name' => 'White Peach Puree', 'ingredient_category_id' => $uncategorized->id, 'strength' => 0.0, 'description' => 'A purée (or mash) is cooked food, usually vegetables, fruits or legumes, that has been ground, pressed, blended or sieved to the consistency of a creamy paste or liquid.', 'user_id' => 1]);
        Ingredient::create(['name' => 'Cream', 'ingredient_category_id' => $uncategorized->id, 'strength' => 0.0, 'description' => 'Cream is a dairy product composed of the higher-fat layer skimmed from the top of milk before homogenization.', 'user_id' => 1]);
        Ingredient::create(['name' => 'Salt', 'ingredient_category_id' => $uncategorized->id, 'strength' => 0.0, 'description' => 'Salt', 'user_id' => 1]);
        Ingredient::create(['name' => 'Pepper', 'ingredient_category_id' => $uncategorized->id, 'strength' => 0.0, 'description' => 'Black pepper', 'user_id' => 1]);
        Ingredient::create(['name' => 'Tabasco', 'ingredient_category_id' => $uncategorized->id, 'strength' => 0.0, 'description' => 'Hot sauce made from vinegar, tabasco peppers, and salt.', 'user_id' => 1]);
        Ingredient::create(['name' => 'Worcestershire Sauce', 'ingredient_category_id' => $uncategorized->id, 'strength' => 0.0, 'description' => 'Fermented liquid condiment created in the city of Worcester', 'user_id' => 1]);
        Ingredient::create(['name' => 'Sugar', 'ingredient_category_id' => $uncategorized->id, 'strength' => 0.0, 'description' => 'White sugar', 'user_id' => 1]);
        Ingredient::create(['name' => 'Egg White', 'ingredient_category_id' => $uncategorized->id, 'strength' => 0.0, 'description' => 'Chicken egg without yolk.', 'user_id' => 1]);
        Ingredient::create(['name' => 'Egg Yolk', 'ingredient_category_id' => $uncategorized->id, 'strength' => 0.0, 'description' => 'Yolk from chicken egg', 'user_id' => 1]);
        Ingredient::create(['name' => 'Coconut Cream', 'ingredient_category_id' => $uncategorized->id, 'strength' => 0.0, 'description' => 'Opaque, milky-white liquid extracted from the grated pulp of mature coconuts.', 'user_id' => 1]);
        Ingredient::create(['name' => 'Vanilla Extract', 'ingredient_category_id' => $uncategorized->id, 'strength' => 0.0, 'description' => 'Solution made by macerating and percolating vanilla pods in a solution of ethanol and water.', 'user_id' => 1]);

        // Bitters
        Ingredient::create(['name' => 'Orange bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 28.0, 'description' => 'Orange bitters is a form of bitters, a cocktail flavoring made from such ingredients as the peels of Seville oranges, cardamom, caraway seed, coriander, anise, and burnt sugar in an alcohol base.', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Angostura aromatic bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 44.7, 'description' => 'Angostura bitters is a concentrated bitters (herbal alcoholic preparation) based on gentian, herbs, and spices, by House of Angostura in Trinidad and Tobago.', 'origin' => 'Trinidad & Tobago', 'user_id' => 1]);
        Ingredient::create(['name' => 'Peach bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 35.0, 'description' => 'Peach bitters flavored with peaches and herbs.', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Angostura cocoa bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 38.0, 'description' => 'Top notes of rich bitter, floral, nutty cocoa with a bold infusion of aromatic botanicals provide endless possibilities to remix classic cocktails.', 'origin' => 'Trinidad & Tobago', 'user_id' => 1]);
        Ingredient::create(['name' => 'Fernet Branca', 'ingredient_category_id' => $bitters->id, 'strength' => 39.0, 'description' => 'Fernet Branca is a bittersweet, herbal liqueur made with a number of different herbs and spices, including myrrh, rhubarb, chamomile, cardamom, aloe, and gentian root.', 'origin' => 'Italy', 'user_id' => 1]);
        Ingredient::create(['name' => 'Peychauds Bitters', 'ingredient_category_id' => $bitters->id, 'strength' => 35.0, 'description' => 'It is a gentian-based bitters, comparable to Angostura bitters, but with a predominant anise aroma combined with a background of mint.', 'origin' => 'North America', 'user_id' => 1]);

        // Liqueurs
        Ingredient::create(['name' => 'Campari', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => 'Italian alcoholic liqueur obtained from the infusion of herbs and fruit.', 'color' => '#ca101e', 'origin' => 'Italy', 'user_id' => 1]);
        Ingredient::create(['name' => 'Aperol', 'ingredient_category_id' => $liqueurs->id, 'strength' => 11.0, 'description' => 'Italian bitter apéritif made of gentian, rhubarb and cinchona, among other ingredients.', 'color' => '#fa4321', 'origin' => 'Italy', 'user_id' => 1]);
        Ingredient::create(['name' => 'Kahlua coffee liqueur', 'ingredient_category_id' => $liqueurs->id, 'strength' => 20.0, 'description' => 'Coffee liqueur made with rum, sugar and arabica coffee.', 'color' => '#1a0d0a', 'origin' => 'Mexico', 'user_id' => 1]);
        Ingredient::create(['name' => 'Amaretto', 'ingredient_category_id' => $liqueurs->id, 'strength' => 24.0, 'description' => 'Sweet almond-flavored liqueur', 'color' => '#d62b0e', 'origin' => 'Italy', 'user_id' => 1]);
        Ingredient::create(['name' => 'Dark Crème de Cacao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => 'Dark brown creamy chocolate-flavored liqueur made from cacao seed.', 'color' => '#0b0504', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'White Crème de Cacao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => 'Milk chocolate flavored liqueur with a hint of vanilla.', 'color' => '#ffffff', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Menthe Crème de Cacao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => 'Mint flavored chocolate liqueur.', 'color' => '#88ad91', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Crème de cassis (blackcurrant liqueur)', 'ingredient_category_id' => $liqueurs->id, 'strength' => 25.0, 'description' => 'It is made from blackcurrants that are crushed and soaked in alcohol, with sugar subsequently added.', 'color' => '#282722', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Crème de Violette', 'ingredient_category_id' => $liqueurs->id, 'strength' => 16.0, 'description' => 'Crème de violette is a delicate, barely-sweet liqueur made from violet flower petals.', 'color' => '#a5a2fd', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Crème de mûre (blackberry liqueur)', 'ingredient_category_id' => $liqueurs->id, 'strength' => 42.3, 'description' => 'Crème de mûre is a liqueur made with fresh blackberries.', 'color' => '#5f1933', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Cointreau', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => 'Orange-flavoured triple sec liqueur.', 'color' => '#ffffff', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Grand Marnier', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => 'Orange-flavored liqueur made from a blend of Cognac brandy, distilled essence of bitter orange, and sugar.', 'color' => '#f34e02', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Suze', 'ingredient_category_id' => $liqueurs->id, 'strength' => 15.0, 'description' => 'Bitter flavored drink made with the roots of the plant gentian.', 'color' => '#ffffff', 'origin' => 'Switzerland', 'user_id' => 1]);
        Ingredient::create(['name' => 'Triple Sec', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => 'Triple sec is usually made from orange peels steeped in a spirit derived from sugar beet due to its neutral flavor. Oranges are then harvested when their skin is still green and they have not fully ripened, so the essential oils remain in the skin and not the flesh of the fruit. This spirit is then redistilled and mixed with more neutral spirit, water, and powdered beet sugar resulting in the final liqueur. This process creates a spirit that has a very strong and distinct orange flavor.', 'color' => '#ffffff', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Maraschino', 'ingredient_category_id' => $liqueurs->id, 'strength' => 32.0, 'description' => 'Liqueur obtained from the distillation of Marasca cherries. The small, slightly sour fruit of the Tapiwa cherry tree, which grows wild along parts of the Dalmatian coast in Croatia, lends the liqueur its unique aroma.', 'color' => '#ffffff', 'origin' => 'Croatia', 'user_id' => 1]);
        Ingredient::create(['name' => 'Galliano', 'ingredient_category_id' => $liqueurs->id, 'strength' => 42.3, 'description' => 'Galliano is sweet with vanilla-anise flavour and subtle citrus and woodsy herbal undernotes.', 'color' => '#caa701', 'origin' => 'Italy', 'user_id' => 1]);
        Ingredient::create(['name' => 'Orange Curaçao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 20.0, 'description' => 'Liqueur flavored with the dried peel of the bitter orange laraha, a citrus fruit grown on the Dutch island of Curaçao.', 'color' => '#edaa53', 'origin' => 'Netherlands', 'user_id' => 1]);
        Ingredient::create(['name' => 'Blue Curaçao', 'ingredient_category_id' => $liqueurs->id, 'strength' => 20.0, 'description' => 'Liqueur flavored with the dried peel of the bitter orange laraha, a citrus fruit grown on the Dutch island of Curaçao.', 'color' => '#0192fe', 'origin' => 'Netherlands', 'user_id' => 1]);
        Ingredient::create(['name' => 'Chambord', 'ingredient_category_id' => $liqueurs->id, 'strength' => 16.5, 'description' => 'Raspberry liqueur modelled after a liqueur produced in the Loire Valley of France during the late 17th century.', 'color' => '#6f1123', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Falernum', 'ingredient_category_id' => $liqueurs->id, 'strength' => 11.0, 'description' => 'Liqueur with flavors of ginger, lime, and almond, and frequently cloves or allspice. It may be thought of as a spicier version of orgeat syrup.', 'color' => '#f4f2e5', 'origin' => 'Caribbean', 'user_id' => 1]);
        Ingredient::create(['name' => 'Green Chartreuse', 'ingredient_category_id' => $liqueurs->id, 'strength' => 55.0, 'description' => 'Green Chartreuse is a naturally green liqueur made from 130 herbs and other plants macerated in alcohol and steeped for about eight hours.', 'color' => '#85993a', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Yellow Chartreuse', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => 'Yellow Chartreuse has a milder and sweeter flavor and aroma than Green Chartreuse, and is lower in alcohol content.', 'color' => '#fbfb4b', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Amaro Nonino', 'ingredient_category_id' => $liqueurs->id, 'strength' => 35.0, 'description' => 'Sweet amaro', 'color' => '#c16e4b', 'origin' => 'Italy', 'user_id' => 1]);
        Ingredient::create(['name' => 'Drambuie', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => 'Liqueur made from Scotch whisky, heather honey, herbs and spices.', 'color' => '#ea7e00', 'origin' => 'Scotland', 'user_id' => 1]);
        Ingredient::create(['name' => 'Bénédictine', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => 'Herbal liqueur flavored with twenty-seven flowers, berries, herbs, roots, and spices.', 'color' => '#f39100', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Pernod', 'ingredient_category_id' => $liqueurs->id, 'strength' => 40.0, 'description' => 'Anise flavored liqueur', 'color' => '#c6c0a0', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Pelinkovac', 'ingredient_category_id' => $liqueurs->id, 'strength' => 32.0, 'description' => 'Pelinkovac is a liqueur based on wormwood, it has a very bitter taste, resembling that of Jägermeister.', 'color' => '#573f42', 'origin' => 'Southeast Europe', 'user_id' => 1]);
        Ingredient::create(['name' => 'Ouzo', 'ingredient_category_id' => $liqueurs->id, 'strength' => 35.0, 'description' => 'Dry anise-flavored aperitif that is widely consumed in Greece.', 'color' => '#ffffff', 'origin' => 'Greece', 'user_id' => 1]);

        // Juices
        Ingredient::create(['name' => 'Lemon juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed lemon juice.', 'color' => '#f3efda', 'user_id' => 1]);
        Ingredient::create(['name' => 'Lime juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed lime juice.', 'color' => '#e9f1d7', 'user_id' => 1]);
        Ingredient::create(['name' => 'Orange juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed orange juice.', 'color' => '#ff9518', 'user_id' => 1]);
        Ingredient::create(['name' => 'Grapefruit juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Freshly squeezed grapefruit juice.', 'color' => '#ed7500', 'user_id' => 1]);
        Ingredient::create(['name' => 'Cranberry juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Juice made from cranberries.', 'color' => '#9c0024', 'user_id' => 1]);
        Ingredient::create(['name' => 'Tomato juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Juice made from tomatoes.', 'color' => '#f16624', 'user_id' => 1]);
        Ingredient::create(['name' => 'Pineapple juice', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Juice from pineapple fruit.', 'color' => '#eadb34', 'user_id' => 1]);
        Ingredient::create(['name' => 'Elderflower Cordial', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Herbal juice made from elderflower.', 'color' => '#d9cfae', 'user_id' => 1]);
        Ingredient::create(['name' => 'Chamomile cordial', 'ingredient_category_id' => $juices->id, 'strength' => 0.0, 'description' => 'Herbal juice made from chamomile.', 'color' => '#e2dccc', 'user_id' => 1]);

        // Beverages
        Ingredient::create(['name' => 'Water', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'color' => '#fff', 'description' => 'It\'s water.', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Club soda', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'color' => '#fff', 'description' => 'Club soda is a manufactured form of carbonated water, commonly used as a drink mixer.', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Tonic', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'color' => '#fff', 'description' => 'Tonic water (or Indian tonic water) is a carbonated soft drink in which quinine is dissolved.', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Cola', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'color' => '#411919', 'description' => 'Cola is a carbonated soft drink flavored with vanilla, cinnamon, citrus oils and other flavorings.', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Ginger beer', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'color' => '#fff', 'description' => 'Ginger beer is a sweetened and carbonated, usually non-alcoholic beverage.', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Espresso', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'color' => '#fff', 'description' => 'Espresso is generally thicker than coffee brewed by other methods, with a viscosity similar to that of warm honey.', 'origin' => 'Italy', 'user_id' => 1]);
        Ingredient::create(['name' => 'Coffee', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'color' => '#fff', 'description' => 'Coffee is a drink prepared from roasted coffee beans.', 'origin' => 'Africa', 'user_id' => 1]);
        Ingredient::create(['name' => 'Orange Flower Water', 'ingredient_category_id' => $beverages->id, 'strength' => 0.0, 'color' => '#fff', 'description' => 'Clear aromatic by-product of the distillation of fresh bitter-orange blossoms for their essential oil.', 'origin' => 'Mediterranean', 'user_id' => 1]);

        // Spirits
        Ingredient::create(['name' => 'Vodka', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ffffff', 'description' => 'Clear alcoholic beverage distilled from cereal grains and potatos.', 'origin' => 'Russia', 'user_id' => 1]);
        $whiskey = Ingredient::create(['name' => 'Whiskey', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#d54a06', 'description' => 'Distilled alcoholic beverage made from fermented grain mash.', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Bourbon Whiskey', 'parent_ingredient_id' => $whiskey->id, 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#d54a06', 'description' => 'Barrel-aged distilled liquor made primarily from corn.', 'origin' => 'North America', 'user_id' => 1]);
        Ingredient::create(['name' => 'Rye whiskey', 'parent_ingredient_id' => $whiskey->id, 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#d54a06', 'description' => 'Whiskey made with at least 51 percent rye grain.', 'origin' => 'North America', 'user_id' => 1]);
        Ingredient::create(['name' => 'Scotch whiskey', 'parent_ingredient_id' => $whiskey->id, 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#d54a06', 'description' => 'Malt whisky or grain whisky (or a blend of the two), made in Scotland.', 'origin' => 'Scotland', 'user_id' => 1]);
        Ingredient::create(['name' => 'Islay Scotch', 'parent_ingredient_id' => $whiskey->id, 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#d54a06', 'description' => 'Scotch whisky made on Islay island.', 'origin' => 'Scotland', 'user_id' => 1]);
        Ingredient::create(['name' => 'Irish whiskey', 'parent_ingredient_id' => $whiskey->id, 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#d54a06', 'description' => 'Whiskey made on the island of Ireland.', 'origin' => 'Ireland', 'user_id' => 1]);
        Ingredient::create(['name' => 'Gin', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ffffff', 'description' => 'Distilled alcoholic drink that derives its flavour from juniper berries.', 'origin' => 'Worldwide', 'user_id' => 1]);
        $brandy = Ingredient::create(['name' => 'Brandy', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#e66500', 'description' => 'Liquor produced by distilling wine.', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Cognac', 'parent_ingredient_id' => $brandy->id, 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#7b1c0a', 'description' => 'A variety of brandy named after the commune of Cognac, France.', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Apricot Brandy', 'parent_ingredient_id' => $brandy->id, 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ca5210', 'description' => 'Liquor distilled from fermented apricot juice or a liqueur made from apricot flesh and kernels.', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Calvados', 'parent_ingredient_id' => $brandy->id, 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ca5210', 'description' => 'Brandy made from apples or pears.', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Tequila', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ffffff', 'description' => 'Distilled beverage made from the blue agave plant.', 'origin' => 'Mexico', 'user_id' => 1]);
        Ingredient::create(['name' => 'Mezcal', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ffffff', 'description' => 'Distilled alcoholic beverage made from any type of agave.', 'origin' => 'Mexico', 'user_id' => 1]);
        Ingredient::create(['name' => 'Absinthe', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#b7ca8e', 'description' => 'Anise-flavoured spirit derived from several plants, including wormwood.', 'origin' => 'France', 'user_id' => 1]);
        Ingredient::create(['name' => 'Gold Rum', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#c79141', 'description' => 'Medium-bodied rum aged in wooden barrels.', 'user_id' => 1]);
        Ingredient::create(['name' => 'White Rum', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ffffff', 'description' => 'Liquor made by fermenting and then distilling sugarcane molasses or sugarcane juice.', 'origin' => 'Caribbean', 'user_id' => 1]);
        Ingredient::create(['name' => 'Demerara Rum', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ca5210', 'description' => 'Rum made with demerara sugar', 'origin' => 'Caribbean', 'user_id' => 1]);
        Ingredient::create(['name' => 'Dark Rum', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ca5210', 'description' => 'Rum made from caramelized sugar or molasses.', 'origin' => 'Caribbean', 'user_id' => 1]);
        Ingredient::create(['name' => 'Jamaican rum', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ca5210', 'description' => 'Rum made in Jamaica.', 'origin' => 'Jamaica', 'user_id' => 1]);
        Ingredient::create(['name' => 'Cachaça', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ffffff', 'description' => 'Distilled spirit made from fermented sugarcane juice.', 'origin' => 'Brazil', 'user_id' => 1]);
        Ingredient::create(['name' => 'Pisco', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ffffff', 'description' => 'Made by distilling fermented grape juice into a high-proof spirit.', 'origin' => 'South America', 'user_id' => 1]);
        Ingredient::create(['name' => 'Peach Schnapps', 'ingredient_category_id' => $spirits->id, 'strength' => 40.0, 'color' => '#ffffff', 'description' => 'Schnapps made from peaches.', 'origin' => 'Worldwide', 'user_id' => 1]);
        Ingredient::create(['name' => 'Grappa', 'ingredient_category_id' => $spirits->id, 'strength' => 50.0, 'color' => '#ffffff', 'description' => 'Fragrant, grape-based pomace brandy.', 'origin' => 'Italy', 'user_id' => 1]);
        Ingredient::create(['name' => 'Rhum agricole', 'ingredient_category_id' => $spirits->id, 'strength' => 50.0, 'color' => '#ffffff', 'description' => 'Rum distilled from freshly squeezed sugarcane juice rather than molasses.', 'origin' => 'Caribbean', 'user_id' => 1]);

        // Syrups
        Ingredient::create(['name' => 'Simple Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Syrup made with sugar and water. Usually in 1:1 or 2:1 ratio.', 'color' => '#e6dfcc', 'user_id' => 1]);
        Ingredient::create(['name' => 'Gomme Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'A thicker simple syrup mixed with arabica gum powder.', 'color' => '#e6dfcc', 'user_id' => 1]);
        Ingredient::create(['name' => 'Orgeat Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Sweet syrup made from almonds, sugar, and rose water or orange flower water.', 'color' => '#d9ca9f', 'user_id' => 1]);
        Ingredient::create(['name' => 'Honey Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Syrup made from dissolving honey in water.', 'color' => '#f2a900', 'user_id' => 1]);
        Ingredient::create(['name' => 'Raspberry Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Fruit syrup made from raspberries.', 'color' => '#b71f23', 'user_id' => 1]);
        Ingredient::create(['name' => 'Grenadine Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Fruit syrup made from pomegranates.', 'color' => '#bb0014', 'user_id' => 1]);
        Ingredient::create(['name' => 'Agave Syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Syrup made from agave.', 'color' => '#deca3f', 'user_id' => 1]);
        Ingredient::create(['name' => 'Donn\'s Mix', 'ingredient_category_id' => $syrups->id, 'description' => '2 parts fresh yellow grapefruit and 1 part cinnamon syrup', 'color' => '#c6972c', 'user_id' => 1]);
        Ingredient::create(['name' => 'Oleo Saccharum', 'ingredient_category_id' => $syrups->id, 'description' => 'Oil extracted from citrus peels by using sugar.', 'color' => '#c6972c', 'user_id' => 1]);
        Ingredient::create(['name' => 'Ginger syrup', 'ingredient_category_id' => $syrups->id, 'description' => 'Syrup made from ginger root.', 'color' => '#c6972c', 'user_id' => 1]);

        // Wines
        Ingredient::create(['name' => 'Sweet Vermouth', 'ingredient_category_id' => $wines->id, 'strength' => 18.0, 'description' => 'Aromatized fortified wine.', 'color' => '#8e4201', 'user_id' => 1]);
        Ingredient::create(['name' => 'Dry Vermouth', 'ingredient_category_id' => $wines->id, 'strength' => 18.0, 'description' => 'Aromatized fortified wine.', 'color' => '#ffffff', 'user_id' => 1]);
        Ingredient::create(['name' => 'White wine', 'ingredient_category_id' => $wines->id, 'strength' => 11.0, 'description' => 'Wine is an alcoholic drink typically made from fermented grapes.', 'color' => '#f6e1b0', 'user_id' => 1]);
        Ingredient::create(['name' => 'Red wine', 'ingredient_category_id' => $wines->id, 'strength' => 11.0, 'description' => 'Red wine is a type of wine made from dark-colored grape varieties.', 'color' => '#801212', 'user_id' => 1]);
        Ingredient::create(['name' => 'Prosecco', 'ingredient_category_id' => $wines->id, 'strength' => 11.0, 'description' => 'Sparkling wine made from Prosecco grapes.', 'color' => '#a57600', 'user_id' => 1]);
        Ingredient::create(['name' => 'Champagne', 'ingredient_category_id' => $wines->id, 'strength' => 12.0, 'description' => 'Sparkling wine.', 'color' => '#f6e1b0', 'user_id' => 1]);
        Ingredient::create(['name' => 'Lillet Blanc', 'ingredient_category_id' => $wines->id, 'strength' => 17.0, 'description' => 'Aromatized sweet wine.', 'color' => '#f7ec77', 'user_id' => 1]);

        $this->info('Attaching images to ingredients...');

        // Create image for every ingredient
        $ingredients = Ingredient::all();
        foreach ($ingredients as $ing) {
            $image = new Image();
            $image->file_path = 'ingredients/' . Str::slug($ing->name) . '.png';
            $image->file_extension = 'png';
            $image->copyright = null;
            $ing->images()->save($image);

            // Update site search index
            $ing->refresh();
            $ing->save();
        }

        $this->info('Finding some cocktail recipes...');

        $this->importIBACocktailsFromJson();

        Artisan::call('scout:import', ['model' => "Kami\Cocktail\Models\Cocktail"]);
        Artisan::call('scout:import', ['model' => "Kami\Cocktail\Models\Ingredient"]);

        SearchActions::updateIndexSettings();

        Model::reguard();

        $this->info('You are ready to serve!');

        return Command::SUCCESS;
    }

    private function importIBACocktailsFromJson()
    {
        $dbIngredients = DB::table('ingredients')->select(['name', 'id'])->get()->map(function ($ing) {
            $ing->name = strtolower($ing->name);

            return $ing;
        });

        $dbGlasses = DB::table('glasses')->select(['name', 'id'])->get()->map(function ($ing) {
            $ing->name = strtolower($ing->name);

            return $ing;
        });

        $source = Yaml::parseFile(resource_path('/data/iba_cocktails_v0.1.0.yml'));

        foreach ($source as $sCocktail) {
            DB::beginTransaction();
            try {
                $cocktail = new Cocktail();
                $cocktail->name = $sCocktail['name'];
                $cocktail->description = $sCocktail['description'];
                $cocktail->instructions = $sCocktail['instructions'];
                $cocktail->garnish = $sCocktail['garnish'];
                $cocktail->source = $sCocktail['source'];
                $cocktail->user_id = 1;

                // Glass
                if (!$dbGlasses->contains('name', strtolower($sCocktail['glass']))) {
                    dump('Glass not found: [' . $sCocktail['name'] . '] ' . $sCocktail['glass']);
                }
                $cocktail->glass_id = $dbGlasses->filter(fn ($item) => $item->name == strtolower($sCocktail['glass']))->first()->id ?? null;
                
                $cocktail->save();

                $image = new Image();
                $image->file_path = 'cocktails/' . Str::slug($sCocktail['name']) . '.jpg';
                $image->file_extension = 'jpg';
                $image->copyright = $sCocktail['image_copyright'] ?? null;
                $cocktail->images()->save($image);

                foreach ($sCocktail['tags'] as $sCat) {
                    $tag = Tag::firstOrNew([
                        'name' => $sCat,
                    ]);
                    $tag->save();
                    $cocktail->tags()->attach($tag->id);
                }

                foreach ($sCocktail['ingredients'] as $sIngredient) {
                    if (!$dbIngredients->contains('name', strtolower($sIngredient['name']))) {
                        dump('Ingredient not found: [' . $sCocktail['name'] . '] ' . $sIngredient['name']);
                        continue;
                    }
                    $dbId = $dbIngredients->filter(fn ($item) => $item->name == strtolower($sIngredient['name']))->first()->id;

                    $cocktailIng = new CocktailIngredient();
                    $cocktailIng->cocktail_id = $cocktail->id;
                    $cocktailIng->ingredient_id = $dbId;
                    $cocktailIng->amount = floatval($sIngredient['amount']);
                    $cocktailIng->units = strtolower($sIngredient['units']);
                    $cocktailIng->optional = $sIngredient['optional'];
                    $cocktailIng->save();
                }

                $cocktail->refresh();
                $cocktail->save();
            } catch(Throwable $e) {
                DB::rollBack();
            }
            DB::commit();
        }
    }
}
