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
