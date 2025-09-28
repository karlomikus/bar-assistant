# v5.8.1
## Fixes
- Fixed missing API ability on `import/cocktail` endpoint

# v5.8.0
## New
- Added `bars/{id}/sync-datapack` endpoint
    - This endpoint will sync existing bar data with the default datapack
    - Existing recipes and ingredients will not be overwritten, only new data will be added

## Changes
- Recommendations now take into account bar shelf ingredients, recipe recency and negative tags

# v5.7.0
## New
- Import parent cocktails via datapack
- Added `is_public` property to `Bar` schema
    - If set to `true`, bar will expose public endpoints `/public/{barId}/*`
    - New public endpoints are still being documented
- Added optional `html_content` to scrape request schema
    - This allows you to send raw HTML content to scrape endpoint, instead of server making a request to the URL
- Scraping now respects `robots.txt` rules
- Scraping is now done with identifiable user agent

# v5.6.1
## Fixes
- Fixed `parent_cocktail_id` type error when importing data via datapack

# v5.6.0
## New
- Added cocktail recipe parent ID tracking
    - Enables recipe varieties/riffs
    - Copying recipe will automatically reference the copied recipe as a parent cocktail
    - Added `parent_cocktail_id` to `Cocktail` schema
    - Added `varieties` to `Cocktail` schema
- Added cocktail recipe year

## Fixes
- Fixed missing detailed error message when scraping failes
- Fixed calculator import error on null values

# v5.5.1
## Fixes
- Fixed missing cocktail recipes with duplicated names in datapack export
- Fixed total missing ingredients counting when filtering by ingredient

# v5.5.0
## New
- Added images upload support to glass types
- Added `supports_recipe_import` to feed item

## Fixes
- Added missing glass volume and volume units to datapack export

# v5.4.2
## Fixes
- Fixed menu cocktails not getting removed correctly

# v5.4.1
## Fixes
- Fixed recipe matching when complex ingredient includes multiple ingredient parts

# v5.4.0
## New
- Added Zitadel as SSO provider
- ABV now gets recalculated when method dilution percentage changes

## Fixes
- Fix materialized path migration failing in certain cases

# v5.3.0
## New
- Added PocketId as a SSO provider
```
# Setup via:
POCKETID_BASE_URL=
POCKETID_CLIENT_ID=
POCKETID_CLIENT_SECRET=
POCKETID_REDIRECT_URI=
```
- Added import support for `cocktailexplorer.co`

# v5.2.5
## Fixes
- Fixed menu item having wrong thumb path

# v5.2.4
## Fixes
- Fixed wrong thumbnail URL getting synced into search engine

# v5.2.3
## Changes
- Query string `state` is always being sent when using Authelia as SSO provider

# v5.2.2
## Changes
- Automatic ingredient units filler function now tries to convert to default bar unit

# v5.2.1
## Fixes
- Fixed OpenAPI schema not being in sync with some responses

# v5.2.0
## New
- Added `ENABLE_PASSWORD_LOGIN` variable
    - You can now disable password login by setting this to false
- Added external feeds support
    - We now collect RSS feeds from popular cocktail related websites and show recipes and news items
    - Currently in beta, can be enabled with `ENABLE_FEEDS=true` env variable
- Added `units` to ingredients
    - Used to define default units for specific ingredients
- Added more `info` and `warning` level logs to authentication
- Added `is_feeds_enabled` and `is_password_login_enabled` to `ServerVersion` schema
- Added `bars.read` and `bars.write` to personal access token abilities

## Changes
- Search index sync refresh no longer happens automatically on docker (re)start
    - You should now run it manually or via bar optimize endpoint

## Fixes
- Fixed missing `images` on `BarRequest` schema
- Fixed missing properties on `Ingredient` schema

# v5.1.0
## New
- Added `thumb_url` to `Image` schema
- Added `image` to `CocktailBasic` and `IngredientBasic` schemas
- Added POST `/bars/{id}/optimize` method
    - Used to run some optional commands per bar like, recalculating ABV, refreshing bar search index, recalculating ingredient hierarchy
- Added cocktail tag index

## Fixes
- Fixed `Menu` and `MenuRequest` schemas being out of sync
- Fixed duplicated queries when fetching descendants

# v5.0.4
## Fixes
- Fixed cast error when duplicating recipe

# v5.0.3
## Fixes
- Optimized ancestors query execution

# v5.0.2
## Fixes
- Added missing menu schema changes

## Changes
- Added route parameters type validation

# v5.0.1
## Fixes
- Fixed SQL error when filtering by bar ingredients

# v5.0.0
⚠️ This is a new major release. This updated includes big database schema updates that are not backwards compatible. I tried to cover all possible issues related to data migration but I recommend that you backup your current database before updating.

Here's a quick summary of the more interesting changes:

- Added SSO support
- Added nested ingredient hierarchy and removed ingredient categories. You can now assign parent child relationships for ingredients and use all descendants of a given ingredient as a possible substitute. This gives option for users to manage more complex ingredient taxonomies, like "Spirits > Rum - Blended > Rum - Blended Lightly Aged"
- Various query optimizations, the app should now be faster and more efficient on large datasets

# Breaking changes
- Removed ingredient categories
    - Existing ingredient categories will be migrated to nested ingredient hierarchy, with ingredient category as the root node
    - Removed `category_id` from ingredient filters
- Removed "Track parent ingredients as a substitutes" option
    - This is now default behavior
- Updated Menu schema
    - Added MenuItem schema to support multiple menu item types
- Removed `bar_id` query param support
    - Use `Bar-Assistant-Bar-Id` request header instead

## New
- Added nested ingredient hierarchy
    - Updated `Ingredient` and `CocktailIngredient` schemas to reflect the new structure
    - All descendants of a given ingredient are now considered as possible substitutes
    - You can specify if you want to use all descendants of a given ingredient as a possible substitute
    - Max nesting level is 10
    - Added `ingredients/{id}/tree` endpoint to show ingredient hierarchy
    - Added `bar:rebuild-hierarchy {barId}` artisan command to rebuild ingredient hierarchy
    - Added `parent_ingredient_id`, `descendants_of` filters to `ingredients` endpoint
- You can now add ingredients to the menu
- Public menu schema now contains item images and bar shelf status
- Added SSO support
    - You can now login via SSO if the server is properly configured
    - Currently supports: Google, GitHub, GitLab, Keycloak, Authentik, Authelia
    - Added GET `sso/providers` endpoint to list available SSO providers
    - Added GET `sso/{provider}/redirect` endpoint to redirect to SSO provider
    - Added GET `sso/{provider}/callback` endpoint to handle SSO callback
    - Added DELETE `profile/sso/{provider}` endpoint to remove SSO credentials from user
    - Updated `Profile` schema
- Added `recommender/cocktails` endpoint
    - You can now get recommendations based on your favorite cocktails
- Added user profile settings
    - Used to store default settings like language and theme
- Added support for default bar currency
- Added `sugar_g_per_ml`, `acidity`, `distillery` properties to `Ingredient` schema

## Changes
- Updated Laravel to version 12
- Import `source` property can now be JSON object

# v4.4.2
## Changes
- Publish image to GitHub Container Registry

## Fixes
- Fix security issue in dependencies

# v4.4.1
## Changes
- You can now use `*` in `METRICS_ALLOWED_IPS` to allow all IPs
- Calculators are now included in `datapack` export

## Fixes
- Fixed diffords guide scraper missing instructions

# v4.4.0
## New
- Added calculators, unleash your inner Kevin Kos
    - Add inputs and formulas to calculate your favorite syrups, batches, juices, etc.
    - Added `/calculators` endpoint
    - Added `calculator_id` to ingredient schema
- Added scrape support for recipes from `Crafted Pour`
- Added `random` sort attribute to `/cocktails` endpoint
- Added prometheus metrics
    - Added `/metrics` endpoint
    - Added `METRICS_ENABLED` environment variable
        - To enable metrics redis is required
    - Added `METRICS_ALLOWED_IPS` environment variable
        - If you enable metrics, you need to specify allowed IPs to access the `/metrics` endpoint
        - Define multiple IPs separated by commas
    - Added `php artisan bar:clear-metrics` command
    - Added `provisioning_bars_total` metric
    - Added `active_bars_total` metric
    - Added `deactivated_bars_total` metric
    - Added `active_users_total` metric
    - Added `api_request_processing_milliseconds` metric

# v4.3.2
## Fixes
- Improve error handling when scraping recipes from unsupported sites

# v4.3.1
## Fixes
- CSV import now matches categories by case-insensitive name

# v4.3.0
## New
- Added `/import/ingredients` endpoint to import multiple ingredients from a CSV file
- Added `is_favorited` to cocktail schema
- Added filter by `name` to `/cocktail-methods` endpoint
- Added support for Sentry error reporting via `SENTRY_LARAVEL_DSN` env variable
- Added `bar:migrate-shelf` command for server owners
- Added `org.opencontainers.image.source` label to docker image

## Fixes
- Properties `in_shelf` and `in_bar_shelf` on cocktail schema correctly match substitute and complex ingredients
- Fixed unit parsing for scraping and importing cocktails

# v4.2.7

Ignored, mistake in release protocol

# v4.2.6
## Fixes
- Fixed wrong substitutes ingredient id counting

# v4.2.5
## Fixes
- Fixed overcounting ingredients which led to incorrect recipe matching if multiple substitutes were used
- Added missing filter `missing_bar_ingredients` attribute to `/cocktails` endpoint

# v4.2.4
## Changes
- Optimized ingredients list query
- Moved SQLite optimizations from docker entry point to api

## Fixed
- Fixed missing rate limit for imports
- Fixed missing max images validation for cloud instance
- Fixed markdown export encoding special characters
- Fixed missing complex ingredients on import

# v4.2.3
## Fixed
- Fix unhandled exception when menu cocktail has `null` as currency

# v4.2.2
## Fixed
- Login endpoint now requires confirmation if `mail_require_confirmation` is enabled
- Meilisearch tokens are now updated on docker restart only if the key has changed
- Ingredient and Bar `images` now have size validation
- Removed wrong token ability check for export download

# v4.2.1
## Fixed
- Fixed issues with search tokens not clearing correctly
- Improved search indexing on larger datasets
- Fixed personal access token middleware handling
- Price calculations for price per pour should be more accurate now

# v4.2.0
## New
- Added cocktail prices
    - Added `/cocktails/{id}/prices` endpoint
    - Show all calculated prices per price category
    - Automatically converts units to calculate the price if possible
- Added `/menu/export` endpoint
    - Exports menu as CSV
- Added `/bars/{id}/cocktails` endpoint, showing bar shelf cocktails
- Added `/bars/{id}/ingredients/recommend` endpoint, showing next recommended ingredients for bar
- `BasicCocktail` schema now includes `short_ingredients` property
- Bar shelf is included in datapack export
- Bar shelf can be imported from datapack
- Added `total_bar_shelf_ingredients` to stats endpoint
- You can run the app as a worker if you set `APP_ROLE=worker` env variable

## Fixes
- Fixed missing substitute ingredient unit conversion in exports
- Fixed missing max amount when importing from export

## Changes
- Stat `total_bar_shelf_cocktails` now includes extra ingredients if `use_parent_as_substitute` flag is enabled

# v4.1.0
## New
- Added bar shelf
    - Each bar now has its own shelf which can be viewed by every bar member
    - Currently members with Admin and Moderator roles can manage bar shelf
    - Bar shelf will be initially populated with all ingredients that current bar owner has in his shelf
    - Added GET `/bars/{id}/ingredients` endpoint
    - Added POST `/bars/{id}/ingredients/batch-store` endpoint
    - Added POST `/bars/{id}/ingredients/batch-delete` endpoint
    - Added filtering by `bar_shelf` attribute to `/ingredients` and `/cocktails` endpoints
    - Added sorting by `missing_bar_ingredients` attribute to `/cocktails` endpoint
    - Added `total_bar_shelf_cocktails` to `/stats` endpoint
    - Added bar shelf status attributes to `Ingredient` and `Cocktail` response schemas
- You can now add images to Bar resource
    - Allows to upload custom bar logo, for example

## Fixes
- Fallback to "EUR" for unknown currencies in menu (fixes migration issues from v3)

## Changes
- Added validation to endpoints that manage user and bar shelf

# v4.0.4
## Fixes
- Check for shelf ingredients before adding them to the shelf
- Fix for missing directories in docker entrypoint
- Fix export id generation

# v4.0.3
## Changes
- Added prod target to docker image

## Fixes
- Fixed wrong endpoint name

# v4.0.2
## Fixes
- Fixed a bug where the bar import command checked the wrong filepath

# v4.0.1
## Fixes
- Fixed a bug where cocktails with substitutes + any other filter were not being filtered correctly

# v4.0.0
This is a new major release. Here's a quick summary of the more interesting changes:

- Docker image is now run as an unprivileged user by default. This has various implications, so please check the migration guide for more info
- Improved API endpoint naming and structure
- Added public Bar Assistant JSON schema specification for cocktail recipes
- Data exporting is now available in multiple formats

[Migration guide](https://docs.barassistant.app/setup/migrate-to-40/)

## Breaking changes
- Minimal PHP version is now 8.3
- Removed `imagick` PHP extension
- New extensions required: `ffi`
- Removed importing cocktails from collections
- Removed importing data from Bar Assistant v2
- List all cocktails and ingredients endpoints don't have default includes anymore
    - Refer to OpenAPI documentation for a list of available includes
- Shelf
    - Moved `/shelf/ingredients` to `/users/{id}/ingredients`
        - Changed response schema, now returns `IngredientBasic`
        - Supports pagination
    - Moved `/shelf/cocktails` to `/users/{id}/cocktails`
        - Changed response schema, now returns `CocktailBasic`
        - Supports pagination
    - Moved `/shelf/cocktails/favorites` to `/users/{id}/cocktails/favorites`
        - Changed response schema, now returns `CocktailBasic`
        - Supports pagination
- Removed `cocktails` property from `Ingredient` schema
    - Use `/ingredients/{id}/cocktails` endpoint instead
    - Supports pagination
- Moved `/ingredients/recommend` to `/users/{id}/ingredients/recommend`
- Grouped `/login`, `/logout`, `/register`, `/forgot-password`, `/reset-password` and `/verify/{id}/{hash}` endpoints into `/auth`
- Shopping list
    - Moved `/shopping-list` to `/users/{id}/shopping-list`
        - Updated `ShoppingList` schema
- Collections
    - Removed PUT `/collections/{id}/cocktails/{cocktailId}` endpoint
    - Removed `/collections/{id}/share` endpoint
    - Removed DELETE `/collections/{id}/cocktails/{cocktailId}` endpoint
    - Removed PUT `/collections/{id}/cocktails/{cocktailId}` endpoint
    - Changed POST `/collections/{id}/cocktails` endpoint to PUT `/collections/{id}/cocktails`
    - Moved GET `/collections/shared` to GET `/bars/{id}/collections`
- Ratings
    - Moved `/ratings/cocktails/{id}` to `/cocktails/{id}/ratings`
    - Changed POST ratings response status code to 201
- Menu
    - Changed `price` attribute in menu request from `string` to `integer`
- Exports
    - Added POST `/exports/{id}/download` endpoint
        - Used to generate a download link for an export
    - Added GET `/exports/{id}/download` endpoint
        - Now requires a token and an expiration date
        - Authorization is not required anymore
        - Used to download an export
- Import
    - Importing now requires a valid JSON schema
    - Removed `save` parameter from `/import/cocktail` endpoint
- Removed `main_image_id` from cocktail model
    - Use `images.sort` instead
- Removed `main_image_id` from ingredient model
    - Use `images.sort` instead
- Stats
    - Moved `/stats` to `/bars/{id}/stats`

## New
- Introduced `Bar-Assistant-Bar-Id` header to specify bar id
    - Used for all endpoints that require bar id
    - You can still send `bar_id` query parameter when needed, but it is considered deprecated
- Added `/ingredients/{id}/cocktails` endpoint, lists all cocktails that use this ingredient
    - Includes cocktails that use this ingredient as a substitute
- Added `/ingredients/{id}/substitutes` endpoint, lists all ingredients that are used as a substitute for this ingredient in cocktail recipes
- Introduced new export recipe schema
- Added `quantity` to shopping list ingredients
- Images are now converted to WebP format before saving to disk
- Added `/images` endpoint, this endpoint is used to list all user uploaded images
- Bar search tokens are now saved in database
- Added `/import/scrape` endpoint
    - This endpoint is used to extract recipe data from a website
    - Result is a JSON schema that can be imported via `/import/cocktail` endpoint
    - Images in response are now base64 encoded. This allows more flexibility in image handling for the clients
- Added `used_as_substitute_for` and `can_be_substituted_with` to ingredient response

## Changes
- Recommended ingredients now take complex ingredients into consideration
- Image processing moved from Imagick to Vips
    - This now requires `libvips` to be installed on the server
        - You can install it via `apt-get install -y --no-install-recommends libvips42`
    - This now requires `ffi` PHP extension to be installed, and some .ini tweaks
- Meilisearch client API keys are now generated via artisan command
- All dates in responses are now in ISO 8601 format
- You can now update slug of existing bar

# v3.19.1
## Fixes
- Fix missing base data in recipes export

# v3.19.0
## New
- Updated the way OpenAPI specification is generated
    - Now generated directly from the code
    - Should improve sync between docs and implementation
    - Updated docs rendering

## Fixes
- Improved complex ingredient matching
- Fixed wrong matching cocktails when parent ingredient tracking is enabled

# v3.18.0
## New
- Added price categories
    - Categorize prices by currency and name
- Added ingredient prices
    - Add price per unit
    - Assign price categories
- Added `json+ld` cocktail share type

## Changes
- Added `intl` PHP extension to docker images

# v3.17.1
## Fixes
- Fix glass volume not nullable #301

# v3.17.0
## New
- Added support for complex ingredients
    - Complex ingredient is ingredient that contains other ingredients
    - If you have all ingredients that are a part of complex ingredient, that complex ingredient will be automatically matched in your shelf
    - For example: If you have a "Lemon", you should also match "Lemon juice"
- Ingredients now include `in_shelf`, `in_shelf_as_substitute`, `in_shelf_as_complex_ingredient` attributes to show how are they matched in your shelf

## Fixes
- Added ingredient bar ownership validation when adding complex and cocktail ingredients

## Changes
- Punchdrink scraper now scrapes "Editors note" part of the recipe and includes it into description

# v3.16.0
## New
- Upgraded framework to Laravel 11
- Added `bar:merge-ingredients` command
    - Used for merging multiple ingredients into one

## Changes
- Improved ImbibeMagazine scraper, now supports recipes in "older" format
- Optimized ingredients list endpoint DB queries
- Optimized similar cocktails list endpoint DB queries
- [Internal] Moved to Laravel Pint for code style
- [Internal] Imagick extension is now used int tests
- [Internal] Tests are now run in parallel in CI

## Fixes
- Fixed a crash when importing ingredients from array without sort attribute

# v3.15.0
## New
- Added bar settings
    - Currently supports `default_units` and `default_lang`
- You can change units used in collection CSV, markdown and text share by passing query string `units` with one of the following units: `ml`, `cl`, `oz`
- CocktailIngredient schema now includes converted and formatted values for: `ml`, `cl`, `oz`
- Added `volume` to `Glass` schema
- Added `volume_units` to `Glass` schema

## Changes
- Updated cocktail text share format

# v3.14.0
## New
- Added more logging to docker runtime
- Add POST `bars/{id}/transfer` endpoint
    - This is used to transfer bar ownership to another user

## Changes
- Updated the way permissions are handled in when bar assistant container starts.
    - This should improve startup/restart time after the initial setup
- Allow scraping and ratings in demo environment
- Hide tokens in demo environment

# v3.13.2
## Fixes
- Fix fatal error while importing unknown JSON format as a collection
- Fix missing unique IDs for resources with same name when creating exports
- Target master branch

# v3.13.1
## Fixes
- Fix fatal error while importing unknwon JSON format as a collection
- Fix missing unique IDs for resources with same name when creating exports

# v3.13.0
## New
- Added `volume_ml` property to cocktail schema
    - Shows approximate total drink volume
- Added `alcohol_units` property to cocktail schema
    - Shows approximate drink alcohol units
- Added `calories` property to cocktail schema
    - Shows approximate drink calories
- Added "KindredCocktails" scraper

## Fixes
- ABV calculation now supports all standard units
- "CocktailParty" scraper now correctly shows source ingredient string
- Bar export files now correctly get cleaned when deleting a bar
- Fixed recipes failing to import when ingredients were missing a category

# v3.12.1
## Fixes
- "CocktailParty" scraper now correctly parses units
- Other minor scraping fixes

# v3.12.0
## New
- Added `/exports` endpoints
    - With this endpoint you can now manage recipe exporting for specific bars
- Added `specific_ingredients` cocktails filter
    - This will show recipes that always contain specific ingredients
- Added `public_id` and `slug` to public menu cocktail response

## Fixes
- Fixed "CocktailParty" scraper

# v3.11.0
## New
- Added POST `/cocktails/{id}/copy` endpoint
- Added POST `/password-check` endpoint

## Changes
- Updated and consolidated cocktail recipe share structure
- Cocktails now sync thumbnail URL to search engine

## Fixes
- Fixed search sometimes not correctly showing all results, for example, searching for `army` would not show `army & navy`

# v3.10.0
## New
- Added `/tokens` endpoint
    - You can create and manage custom Personal Access Tokens
    - Current available token abilities are `cocktails.read`, `cocktails.write`, `ingredients.read` and `ingredients.write`
    - Tokens can have expiration date
- You can now pass optional `token_name` when requesting a new token via `login` endpoint

# v3.9.0
## New
- Added `slug` column to `bars` table
- Added a simple bar menu
    - Added POST and GET `/menu` endpoints
    - Added GET `/explore/menus/{barSlug}` public endpoint
    - This is a simple menu implementation, more features are coming in future
- When exporting and importing recipe data, timestamps are also imported

# v3.8.0
## New
- Added `favorite_tags` to stats endpoint
- Added `--type` option to recipes export
    - Suports `yml` (default) and `json`

## Fixes
- Optimized cocktail shelf query when parent ingredient tracking is on
- Searching by name now also searches slug
- Fixed missing substitute ingredient data on default recipe data import
- Fixed missing parent ingredient data on default recipe data import
- Fixed missing ingredient note data on default recipe data import

# v3.7.0
## New
- Added `missing_ingredients` cocktails filter
- Added `use_parent_as_substitute` setting to bar memberships
    - With this you can toggle using parent ingredient of a specific ingredient as a substitute in your shelf
    - Before, this was available via experimental `PARENT_INGREDIENT_SUBSTITUTE` env variable
- Added `csv` export to cocktail collections
- Added `/ingredients/recommend` endpoint
    - This provides recommendations regarding the additional ingredients that may be added to your shelf to expand potential new cocktail recipes.

## Changes
- Removed `PARENT_INGREDIENT_SUBSTITUTE` env variable
- Update login error message depending on mail confirmation config
- Update min log level to "warning"
- Updated cocktail markdown export format
- JSON export format is now the same as exported YAML recipes

# v3.6.0
## New
- Bar Assistant now has a new cloud offering and homepage: [barassistant.app](https://barassistant.app)
- Added `total_bar_members` count
- You can now edit existing image

# v3.5.2
## Fixes
- Added missing ingredient created and updated timestamps
- Correctly cleanup user images after deleting a user via cli

# v3.5.1
## Fixes
- Add missing endpoints to docs
- Account deleted mail not getting sent correctly

# v3.5.0
## New
- Send transactional email about password change
- Send transactional email about account deletion
- Bars can now have different statuses:
    - `active` - Active bar, default value
    - `deactivated` - Deactivated bar, no one can access it
    - `provisioning` - Bar is currently getting populated with data. Usually set at the start of bar setup and removed after all data has been imported.
- Added new endpoints
    - POST `/bars/{id}/status` - To update bar status
- Images now get resized up to 1400px height max

## Changes
- Update email styling
- Added `bcmath` extension
- Added CORS headers to images
- Importing cocktails now use the same authorization policy as cocktail creation

# v3.4.0
## New
- You can now exclude cocktails by specific ingredients #230
    - Added `filter[ignore_ingredients]=3,7,9` filter to `cocktails` endpoint
- Added `cron` to docker image and enabled Laravel task scheduler

## Changes
- Added support for Meilisearch v1.5
- Default login tokens now expire after 7 days
- Improved scraping support for Liquor.com
- Updated base docker image used as base
- Removed algolia package
- Removed searchable `date` attribute from `cocktails` index
- Removed searchable `origin` attribute from `ingredients` index

## Fixes
- Fixed typos in OpenAPI spec
- Bars can only be deleted by a bar owner (user that created the bar)

# v3.3.1
## Fixes
- Add missing mail confirmation when creating a user via `users` endpoint

# v3.3.0 📧
## New
- You can now send emails from Bar Assistant if you have the correct settings
    - To configure SMTP, set the following ENV variables:
        - `MAIL_HOST=`
        - `MAIL_PORT=`
        - `MAIL_ENCRYPTION=`
        - `MAIL_USERNAME=`
        - `MAIL_PASSWORD=`
    - To request [other drivers that Laravel supports](https://laravel.com/docs/10.x/mail#configuration), open a new github issue
- Added POST `/forgot-password` endpoint
    - You can use this endpoint to request a password change email
    - You must set the reset URL by setting `MAIL_RESET_URL` env variable
        - For example: `MAIL_RESET_URL="https://bar.local/reset-password?token=[token]"`
- Added POST `/reset-password` endpoint
    - You can use this endpoint to reset the password with confirmation key from the email
- Added `MAIL_REQUIRE_CONFIRMATION` env variable, (`false` by default)
    - If you enable this, users will have to verify their accounts via link in a email before authenticating
    - You must set the confirmation URL by setting `MAIL_CONFIRM_URL` env variable
        - For example: `MAIL_CONFIRM_URL="https://bar.local/confirmation/[id]/[hash]"`
    - Added GET `/verify/{id}/{hash}` endpoint
- More information about email settings will be added to docs

## Changes
- Enabled sqlite WAL mode
- From now, passwords can't be shorter than 5 chars
- Improved tag matching, tags are now matched by their lowercase name, and duplicates are ignored
- Removed some properties from a public cocktail endpoint response: `main_image_id`, `images.file_path`, `images.id`.

## Fixes
- Added utensils to exported recipes
- Fixed cocktail utensil migration from v2

# v3.2.0
## New
- Updated base bar cocktails and ingredients
    - Now includes data from https://github.com/bar-assistant/data
- Added `php artisan bar:export-recipes {barId}` command
    - This will export all recipes from a bar in a .yml format, similar to repository mentioned above
- Added `php artisan bar:import-recipes {filename}` command
    - This will import recipe data exported via upper method
- Added `php artisan bar:delete-user {email}` command
    - This will completely delete user and all his data, including cocktails, bars and ingredients he created.

## Changes
- Command `php artisan bar:export-zip` is now deprecated and will be removed in future versions
    - You can only import data from the same version of Bar Assistant that your exported data from
- Command `php artisan bar:import-zip` is now deprecated and will be removed in future versions
    - Passwords and emails are not included by default when exporting data
- Users that delete their account will now be "soft" deleted
    - All their user data will be anonymized
    - All bar memberships and related data will be deleted

## Fixes
- Invisible `&nbsp;` chars are now removed when importing data from scrapers
- Fix some auth errors when updating images

# v3.1.1
## Fixes
- Fix joining wrong columns on bar export leading to import errors #218

# v3.1.0
## New
- Added `bar:export-zip` command
    - You can use this command to export all the data from specific bars
    - For example: `php artisan bar:export 5 7 9` will export all data from bars with id: 5, 7, 9
- Added `bar:import-zip` command
- Now you can share your collections with other bar members
    - Added `/collections/shared` endpoint
    - Added `is_bar_shared` property to collection request

# v3.0.3
## Fixes
- Fix cocktail share endpoint not working if ingredient does not have category

# v3.0.2
## Fixes
- Added missing properties to public recipe (ingredient note and substitute amounts)

# v3.0.1
## Fixes
- Fixed role not updating on user save

## Changes
- Joining a bar will default to "Guest" role instead of "General"

# v3.0.0
## Multiple bars
- Bar Assistant now supports multiple bars.
    - With this change a lot of endpoints now require to have bar reference, this comes in a form of `bar_id` query parameter
    - Please refer to the new schema specification to see what endpoints now require `bar_id` query parameter
- This update also changed a lot database table schemas, so I advise you to create a backup of your data before you update to v3
- Users can be invited or join with invite code to specific bars

## Improved user control
- Users can have one of the following roles in a bar:
    - Guest
        - Rate and favorite cocktails
        - Create personal collections
    - General
        - Everything as "Guest"
        - Can add cocktails and ingredients
    - Moderator
        - Everything as "General"
        - Can not modify bar
        - Can not change user roles
    - Admin
        - Everything as "Moderator"
        - Full access to all bar actions

## Breaking changes
- Updated a lot of schemas, refer to openapi specification to see the changes
- Token response is now wrapped with `data` object like the rest of the endpoints endpoint
- Removed POST `shelf/ingredients` endpoint
- Removed POST `shelf/ingredients/{ingredientId}` endpoint
    - **Upgrade guide**: Use `shelf/ingredients/batch-store` endpoint
- Removed DELETE `shelf/ingredients/{ingredientId}` endpoint
    - **Upgrade guide**: Use `shelf/ingredients/batch-delete` endpoint
- Removed `notes` property from `Cocktail` schema
    - **Upgrade guide**: Use `notes/` endpoint to get users notes
- Removed `glasses/find` endpoint
    - **Upgrade guide**: Use `glasses/` endpoint with `filter[name]` query string
- Removed GET `/images` endpoint
- Removed `bar:make-admin` command
- Removed `bar:open` command
- Removed `bar:refresh-user-search-keys` command
- Removed `bar:import-zip` command
- Removed `bar:export-zip` command
- Removed `bar:scrape` command
- Renamed `/user` endpoint to `/profile`
- Renamed `user_id` filter on `cocktails` endpoint to `created_user_id`
- Renamed `user_id` filter on `ingredients` endpoint to `created_user_id`
- Ingredient category is not required anymore when adding an ingredient

## New
- Added `bars/` endpoint
- Added GET `notes/` endpoint
- Stats now have users top 5 favorite ingredients, calculated from favorite cocktails
- Importing cocktails from collection now has actions on how to handle duplicates
- Added `bar:backup {barId}` command
- Cocktail ingredient now supports variable amounts, you can add max amount with `amount_max` attribute
- Cocktail ingredient now can have a note attached
- Cocktail substitutes now have the following attributes: `ingredient_id`, `amount`, `amount_max`, `units`
- Added options on how to handle duplicated recipes when importing collection
    - Duplicates are matched by recipe name
    - Possible actions:
        - Do nothing
        - Overwrite duplicated
        - Skip duplicates
- Added `average_rating_min` filter to `cocktails` endpoint
- Added `average_rating_max` filter to `cocktails` endpoint

## Changes
- Default database filename changed to `database.ba3.sqlite`
- Optimized base images of cocktails and ingredients
- Cocktail and ingredient images are now categorized in folders by bar id
- Merged all migrations to a single one
- Meilisearch API keys are now generating tenant tokens
- Changed the way slugs are generated, they now include bar id
- Changed what attributes are searchable
    - Removed from `cocktails` index: `garnish`, `image_hash`, `main_image_id`, `user_id`, `glass`, `average_rating`, `main_ingredient_name`, `calculated_abv`, `method`, `has_public_link`
    - Added to `cocktails` index: `bar_id`
    - Removed from `ingredients` index: `strength_abv`, `color`
    - Added to `ingredients` index: `bar_id`

# v2.6.0
## New
- Added export options for version 3: `php artisan bar:export-zip --version3`

## Fixes
- Fixed migrations missing on first start

# v2.5.4
## Fixes
- Fixed notes staying after cocktail delete
- Fixed missing env variable

# v2.5.3
## Fixes
- Fixed null mapping of cocktail substitutes when importing from collection
- Fixed failing migrations due to meilisearch syncing #158

# v2.5.2
## Fixes
- Fixed missing utensil info on GET cocktail endpoint

# v2.5.1
## Fixes
- Fixed missing ratings

# v2.5.0
## New
- Added `markdown` type to share endpoint
- Added `/collections/{id}/share` endpoint
    - You can share your collection as JSON, YAML
- Added `/shopping-list/share` endpoint #124
    - You can share shopping list as markdown
- Added `utensils` #127
    - You can attach required utensils for cocktail recipes
- Added `/ingredients/{id}/extra` #133
    - This will show you how many cocktails you can make if you add ingredient to shelf
- Added next and prev cocktail actions #149
- Added new scrapers
    - Steve The Bartender

## Changes
- Property `ingredients` on `Cocktail` schema is now only shown if you include it with `include=ingredients` query string
- Property `short_ingredients` on `Cocktail` schema removed
- Property `tags` on `Cocktail` schema is now array of objects
- Property `ingredient_category_id` on `Ingredient` schema removed
- Improved website recipe scraping

# v2.4.0
## New
- Added new scrapers
    - CocktailsDistilled
- Added `total_collections` to `StatsResponse` schema
- Added new sort to `ingredients` endpoint: `total_cocktails`
- Removed `user_id` from `Cocktail` schema
- Removed `main_ingredient_name` from `Cocktail` schema

## Deprecations
- Property `short_ingredients` on `Cocktail` schema will be removed in next release
- Property `tags` on `Cocktail` schema will be changed to array of objects in next release, currently `cocktail_tags`
- Property `ingredient_category_id` on `Ingredient` schema will be changed to array of objects in next release, currently `cocktail_tags`
- Property `ingredients` on `Cocktail` schema will be only shown if you are including the relationship via query parameter in the next release

## Fixes
- Fixed typo for `include` query param in specification #147
- Importing recipes via API calls will correctly set cocktail author
- Improved recipe instructions parsing for default parser

# v2.3.0
## New
- Added cocktails count to `tag`, `glass` and `cocktail_method` resources
- Added `abv_min`, `abv_max`, `user_rating_min`, `user_rating_max` and `main_ingredient_id` cocktail query filters
- Added `main_ingredients` ingredient query filter
- Added `shelf_ingredients` cocktail query filter
    - This will return all cocktails you can make with the given ingredients
    - This allows you to create on-the-fly custom shelf cocktails
- Added POST `/collections/{id}/cocktails` endpoint
    - This endpoint allows you to add multiple cocktails to collection with single call
- Added `cocktails` request property to `CollectionRequest` schema
    - This allows you to add cocktails when creating new collection
- Added `include` query option to `cocktails` endpoint
    - This is used to toggle extra available data when fetching cocktails

## Changes
- ABV is now saved in the cocktails table with the cocktail #139
- Default results per page on `cocktails` resource increased to 25

## Deprecations
- Property `user_id` on `Cocktail` schema will be removed in next release, use `user.id` property instead
- Property `main_ingredient_name` on `Cocktail` schema will be removed in next release

## Fixes
- Fixed boolean query filters not correctly filtering the results
- Fixed slugs with only numbers fetching by `id` column first instead of `slug` #140
- Added missing cache clearing after importing data from zip file
- Fixed missing eager load of `user` relation when fetching cocktails
- Fixed old cocktail ingredients not having correct ingredient sorting
- Fixed image extension detection sometimes falling back to jpg for no good reason

# v2.2.0
## New
- Improved schema scraping, and added new scrapers for
    - CocktailParty
    - LiberAndCo
    - DiffordsGuide
    - TheCocktailDB
- Added `user_name` to cocktail response
- Added filters to `/collections` endpoint
- Added `missing_ingredients` sort to cocktails endpoint
    - This will sort by number of user's missing ingredients #137
- Added `/cocktails/{id}/similar` endpoint to show similar cocktails #40

# v2.1.0
## New
- Added support for Meilisearch v1.2
- Added `/import/cocktail` endpoint
    - Supports importing cocktails in YAML and JSON formats
    - Supports importing cocktails from URLs that are supported by built in scrapers
- Added `/cocktail/{id}/share` endpoint
    - Supports sharing cocktails in JSON, YAML, XML and plain text format

## Changes
- Disabled foreign key checks when importing data from zip file #117

# v2.0.0
## Breaking changes
- Minimum supported Meilisearch version is 1.1
    - **Upgrade guide**: If you are using docker bump meilisearch version to 1.1. You can safely delete Meilisearch container and it's volume, Bar Assistant will sync data when you restart the Bar Assistant container
- Changed query parameters for `/cocktails` endpoint
    - **Upgrade guide**: Refer to API spec to see new parameters
- Changed query parameters for `/ingredients` endpoint
    - **Upgrade guide**: Refer to API spec to see new parameters
- Removed `/cocktails/user-favorites` endpoint
    - **Upgrade guide**: Available via `/cocktails?filter[favorites]=true`
- Updated `/cocktails/user-shelf` to return only cocktail ids and moved to `/shelf/cocktails`
    - **Upgrade guide**: Old response schema available via `/cocktails?filter[on_shelf]=true`
- Removed `/ingredients/find` endpoint
    - **Upgrade guide**: Available via `/ingredients?filter[name_exact]=whiskey`
- Removed `/cocktails/random` endpoint
- Updated `/shelf` endpoints to make more sense
    - Moved GET `/cocktails/user-shelf` endpoint to GET `/shelf/cocktails`
    - Moved GET `/shelf` endpoint to GET `/shelf/ingredients`
    - Moved POST `/shelf` endpoint to POST `/shelf/ingredients`
    - Moved POST `/ingredients/{ingredientId}` endpoint to POST `/shelf/ingredients/{ingredientId}`
    - Moved DELETE `/ingredients/{ingredientId}` endpoint to DELETE `/shelf/ingredients/{ingredientId}`
- Moved PUT `/images/{id}` endpoint to POST `/images/{id}`
    - You can now use this method as a pseudo PATCH operation on image resource
    - You can now update the image file
- Removed `site_search_index` indexing
    - **Upgrade guide**: If possible migrate to federated/multi-index search
- Redis is now mandatory dependency
    - **Upgrade guide**: Setup redis with `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT` env variables
- Removed `bar:dump-search` command
    - **Upgrade guide**: Follow migration/upgrade guide for your selected search driver
- Removed `id` from `CocktailIngredient` schema
- Updated `UserIngredient` schema
- Updated `Ingredient` schema
    - Moved `parent_ingredient_id` to `parent_ingredient` object, accessible via `parent_ingredient.id`
- Removed `DISABLE_LOGIN` env variable

## New
- Meilisearch is no longer mandatory dependency for API to work
- Added support for all default Laravel Scout drivers, meaning:
    - You can now use Algolia as your search engine
    - You can now use database as your search engine
- Added `DISABLE_LOGIN` environment variable
    - This will remove the need to authenticate with token to access the api
- Added GET `/images` endpoint
- `ImageRequest` schema now supports `image_url` parameter to upload image from URL
- Added `MAX_USER_BARS` env variable, defaults to 50
    - This limits how many bars can a single user create

## Fixes
- Fixed openapi swagger docs url

## Changes
- Enabled xdebug in local development dockerfile
- Replaced default image processor GD with Imagick

# v1.10.2
## Fixes
- Fix missing array key error on public pages

# v1.10.1
## Fixes
- Fix cocktail ingredient units and amount display

# v1.10.0
# New
- Added notes endpoint
    - Currently only supported for `cocktails` resource
- Added cocktail collections endpoint
- Added new ENV variable: `PARENT_INGREDIENT_SUBSTITUTE`
    - This will take in account parent ingredients when querying what cocktails you can make #95
- Added `total_shelf_ingredients` to stats endpoint

# Changes
- Optimized some SQL queries

# v1.9.0
## New
- Now running on PHP 8.2
- Added support for Meilisearch v1.1
- Added `on_shelf` filter to ingredients list

# v1.8.0
## New
- Added image placeholder hash with ThumbHash algorithm

## Changes
- Moved default zip export path to `storage/bar-assistant/`

# v1.7.0
## New
- Added `public_id` to cocktail schema response
- Added endpoint for removing public link

## Fixes
- Ignore null passwords when updating profile

# v1.6.1

## Fixes
- Increase production upload body size

# v1.6.0
## New
- Added `sort` attribute to image resource

## Changes
- When editing cocktail with new images, Bar Assistant will now add new images instead of overwriting the existing ones
- Increased web server upload file size in docker development environment
- Application version now automatically tagged with github ref

## Fixes
- Fix importing cocktails from the CLI #83

# v1.5.0
## 🔴 Important Notes 🔴

This update includes a big change to docker image configuration:

- Now using PHP-fpm and Nginx
- New volume mapping: `/var/www/cocktails/storage/bar-assistant`
- Improved error logging
- Updated Meilisearch to stable version (1.0)

As such you should follow the migration [guide available here](https://bar-assistant.github.io/docs/setup/updating/).

## Fixes
- Fix type cast issue when adding ingredients to cocktail #77

## Changes
- Updated framework to Laravel 10
- Updated docker image configuration
- Added support for Meilisearch 1.0

# v1.4.1
## Fixes
- Fix max total hits limit for search indexes

# v1.4.0
## New
- Added `/find` endpoint to search resource by name
    - Added to cocktails and ingredients endpoint
- Added `parent_ingredient_id` to ingredient response

# v1.3.3
## Fixes
- Updated packages, including security fix for `symfony/http-kernel`

## Changes
- Updated import and export commands

# v1.3.2
## New
- Set method for existing cocktails without method
    - This syncs stock cocktail data with existing data

# v1.3.1
## Fixes
- Fix permission bug introduced by copying app files in docker

# v1.3.0
## New
- Added cocktail ABV, calculated from cocktail ingredients
- Added cocktail methods
- Added command to refresh meilisearch user API keys
- Recipe scraping
    - Added `/scrape/cocktail` endpoint
    - BA will now try to parse JSON+LD schema and microdata if no specific site parser exists
    - Support for TheDrinkBlog
- Added automatic dev docker images building

## Fixes
- Fix some scraper issues
- Fix Imbibe scraper issue with instructions counter

## Changes
- Updated order of some base cocktail ingredients
- Added zip extension to dockerfile

## WIP
Some of this features are available but still need documenting and testing

- Added experimental features like exporting and import recipes between BA instances
- Added `/cocktails/{id}/make-public` method
    - This will create a publicly accessible cocktail data at `/explore/cocktails/{ulid}`

# v1.2.0

## New
- Added `/stats` endpoint
- Added `order_by` query parameter to `/cocktails` endpoint
- Added `limit` query parameter to `/cocktails/user-shelf` endpoint
- Added `limit` query parameter to `/ingredients` endpoint
- Added `created_at` attribute to cocktail response schema

## Fixes
- Updating tags now reflects changes in cocktail index

## Changes
- Increased cocktail thumbnail size to 400x400
- Updated ingredients index
    - Added `strength_abv`, `color`, `origin`

# v1.1.0
This update includes changes to authorization. Users can now be admins and have elevated privilages. Since this is a new change you will not have any admins in your instance if you are not starting from a fresh install. To make yourself admin you need to run the following command with the email you are using to login:

``` bash
$ php artisan bar:make-admin your@email.com
```
Or in docker/compose:

``` bash
$ docker compose exec -it bar-assistant php artisan bar:make-admin your@email.com
```

## New
- Added `users` endpoint to manage users
- User can now have admin role
    - Added command to convert a single user to admin
- Implemented authorization
    - Management of users is allowed only to admins
    - Update and delete of glass type allowed only to admins
    - Update and delete of ingredient categories allowed only to admins
    - All cocktails and ingredients created by the system can only be updated and delete by admin
- Added ingredient parser
    - Allows easier extraction of scraped data
- Recipe scraping
    - Support for Haus Alpenz
- Sync site search index on docker start
- Added `main_ingredient_name` to cocktail response schema
    - Ingredients that are first added to cocktail are considered as main ingredient
- Added development docker compose file
- Added `limit` query attribute to cocktail favorites endpoint

## Changes
- Added `user_id` to `images` table
- Add flush index option to `bar:refresh-search` command
- Optimized cocktail favorites query

# v1.0.5
## New
- Added `/tags` endpoint to manage tags
- Added cocktail ratings
    - Added `/ratings` endpoint to manage ratings
    - Added average rating attribute to cocktails index

# v1.0.4
## New
- Recipe scraping
    - Support for Imbibe Magazine
    - Support for Eric's Cocktail Guide
    - Add `-d` option to dump data without importing
- Add Meilisearch dump index command
    - This will help with future Meilisearch updates

## Fixes
- Fix cocktail image overwriting when cocktails have the same name
- Delete user API tokens when changing the password

# v1.0.3
## Fixes
- Sync cocktails and ingredients with meilisearch after docker restart
- Bump version in openapi spec

# v1.0.2
## New
- Add `php artisan bar:scrap` command to scrape recipes from the supported websites
    - Support for TuxedoNo2
    - Support for A Couple Cooks
- Add cocktails thumbnail generation endpoint
- Enabled GD extension in docker

## Fixes
- Sort ingredient categories by name
- Sort related cocktails in ingredient resource by name
- Escape ingredient description

## Changes
- Use `docker-php-extension-installer` for docker image

# v1.0.1
## New
- Make cocktail `id` attribute filterable in cocktails index
- Add `per_page` query parameter to cocktails endpoint (defaults to 15)
- Add profile update endpoint
- Search index settings are updated on docker restart

## Fixes
- Sort shelf cocktails by name
- Document missing query parameters in OA specification

# v1.0.0
- Cover all endpoints with tests
- Add coding style

# v0.5.3
- Update OpenApi spec and endpoints
- Delete responses now return 204
- Add contract testing
- Update related ingredients

# v0.5.2
- Add response caching, disabled by default
- Cache docker image steps in GH actions

# v0.5.1
- Add cascading deletes for some foreign keys

# v0.5.0
- Use redis for session and cache
- Automatically select some ingredients when running the application for the first time
- Add OpenAPI specification and `/docs` route
- Fixed an error response when adding ingredient to the shelf from shopping list
- Updated some endpoints to be more consistent
- Include substitute ingredients when showing a list of shelf cocktails
- Added debugbar
- Remove the need to run `chown` in docker container
- Add demo environment support

# v0.4.1
- Enable opcache in docker image
- Cache route and config in docker image

# v0.4.0
- Refactor image uploading and handling
- Updates for some base ingredients
- Add more popular cocktails
- Finish cocktail ingredient substitutes endpoints
- Finish glass type endpoints

# v0.3.0
- Move "Uncategorized" ingredient category to id: 1
- Cocktail save/update methods now save glass type
- Update docker entrypoint with better up/down handling
- Use `laravel/sluggable` package for ingredient and cocktail slug generation
- Added currently used Meilisearch host to user endpoint response
- Update Meilisearch cocktail index settings
- Add `user_id` to cocktail index
- Implement ingredient varieties feature #6
- Increased API request rate limiting
- Add cocktail ingredient substitutes feature
- Add a new cocktail source list
- Exclude optional cocktail ingredients from shelf cocktail matching
- Implement all ingredient category resource endpoints
- Update postman collection
- Implement more tests

# v0.2.0
- Add docker build action
- Add glasses
- Update base cocktail db
- Update error handling
- Add logout

# v0.1.0
- Initial release
