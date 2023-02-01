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
