# v3.0.0
## Multiple bars
- Bar Assistant now supports multiple bars.
    - With this change a lot of endpoints require to have bar reference, this comes in a form of `bar_id` query parameter
    - Please refer to the new schema specification to see what endpoints now require `bar_id` query parameter
- This update also changed a lot database table schemas, and having automatic data migration would take a lot of time to implement
    - To migrate your old data you will have an option to upload v2 .zip file when creating a new bar
- Users can be invited or join with invite code to specific bars

## Improved user control
- Users now have more available roles:
    - Admin: TODO
    - Moderator: TODO
    - General: TODO
    - Guest: TODO

## Breaking changes
- Removed POST `shelf/ingredients/{ingredientId}` endpoint
    - **Upgrade guide**: Use `shelf/ingredients/batch-store` endpoint
- Removed DELETE `shelf/ingredients/{ingredientId}` endpoint
    - **Upgrade guide**: Use `shelf/ingredients/batch-delete` endpoint
- Removed GET `/images` endpoint
- Removed `bar:make-admin` command
- Removed `bar:open` command
- Removed `bar:refresh-user-search-keys` command

## New
- Added `bars/` endpoint
- Stats now have users top 5 favorite ingredients, calculated from favorite cocktails

## Changes
- Optimized base images of cocktails and ingredients
- Cocktail and ingredient images are now categorized in folders by bar id
- Merged all migrations to a single one
- Meilisearch API keys are now generating tenant tokens
- Changed what data is synced to search servers
    - TODO

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

## New
- Meilisearch is no longer mandatory dependency for API to work
- Added support for all default Laravel Scout drivers, meaning:
    - You can now use Algolia as your search engine
    - You can now use database as your search engine
- Added `DISABLE_LOGIN` environment variable
    - This will remove the need to authenticate with token to access the api
- Added GET `/images` endpoint
- `ImageRequest` schema now supports `image_url` parameter to upload image from URL

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
