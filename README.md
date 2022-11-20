<p align="center">
    <a href="https://karlomikus.com" target="_blank"><img src="resources/art/logotype.svg" alt="Bar assistant Logo" width="300"></a>
</p>

<p align="center">
    <a href="https://hub.docker.com/r/kmikus12/bar-assistant-server"><img src="https://img.shields.io/docker/v/kmikus12/bar-assistant-server?style=for-the-badge" alt="Docker image"></a>
    <img src="https://img.shields.io/github/license/karlomikus/bar-assistant?style=for-the-badge" alt="Docker image">
    <img src="https://img.shields.io/github/workflow/status/karlomikus/bar-assistant/Test%20application?style=for-the-badge" alt="Docker image">
</p>

## 🍸 Bar assistant

Bar assistant is a self hosted application for managing your home bar. It allows you to add ingredients and create custom cocktail recipes.

This repository only contains the API server, if you are looking for easy to use web client, take a look at [Salt Rim](https://github.com/karlomikus/vue-salt-rim).

Note: This application is still in development and there will be breaking changes and loss of data. I do not recommend using this in a "production" environment until a stable version is released.

## Features

- Includes all current IBA cocktails
- Over 100 ingredients
- Endpoints for managing of ingredients and cocktails
- Mark ingredients you have and get all cocktails that you can make
- Detailed cocktail and ingredient information
- Ability to upload and assign images
- Shopping list for missing ingredients
- Automatic indexing of data in Meilisearch
- Ingredient substitutes

## Planned features

- Cocktail recipe sharing
- User defined cocktail collections
- Cocktail ratings
- Add user notes to cocktail
- Add cocktail flavor profiles
- Cocktail recipe scraping

## Installation

This application is made with Laravel, so you should [follow installation instructions](https://laravel.com/docs/9.x/deployment) for a standard Laravel project.

### Requirements:

- PHP >=8.1
- Sqlite 3
- Working [Meilisearch server](https://github.com/meilisearch)
- (Optional) Redis server

### Meilisearch

Bar Assistant is using Meilisearch as a primary Scout driver. It's used to index cocktails and ingredients used for filtering and full text search.

### Setup

After cloning the repository, you should do the following:

1. Update your environment variables

``` bash
$ cp .env.dist .env
```

``` env
# Your application instance URL
APP_URL=
# Meilisearch instance URL
MEILISEARCH_HOST=
# Meilisearch search key
MEILISEARCH_KEY=
# If using redis, the following
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

2. Run the commands
``` bash
# Generate a key
$ php artisan key:generate

# To setup correct image paths
$ php artisan storage:link

# To setup the database:
$ php artisan migrate

# To fill the database with data
$ php artisan bar:open
```

Default login information is:

- email: `admin@example.com`
- password: `password`

## Docker

[Also checkout how to setup the whole Bar Assistant stack here.](https://github.com/karlomikus/vue-salt-rim#docker-compose)

``` bash
docker run -d \
    -e APP_URL=http://localhost:8080 \
    -e MEILISEARCH_HOST=http://localhost:7700 \
    -e MEILISEARCH_KEY=maskerKey \
    kmikus12/bar-assistant-server
```

## Contributing

TODO

## License

The Bar Assistant API is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
