<p align="center">
    <a href="https://karlomikus.com" target="_blank"><img src="resources/art/logotype.svg" alt="Bar assistant Logo" width="300"></a>
</p>

<p align="center">
    <a href="https://hub.docker.com/r/kmikus12/bar-assistant-server"><img src="https://img.shields.io/docker/v/kmikus12/bar-assistant-server?style=for-the-badge" alt="Docker image"></a>
    <img src="https://img.shields.io/github/license/karlomikus/bar-assistant?style=for-the-badge" alt="Docker image">
    <img src="https://img.shields.io/github/workflow/status/karlomikus/bar-assistant/Test%20application?style=for-the-badge" alt="Docker image">
</p>

## 🍸 Bar assistant

Bar assistant is a self hosted application for managing your home bar. It allows you to add your ingredients, search for cocktails and create custom cocktail recipes.

This repository only contains the API server, if you are looking for easy to use web client, take a look at [Salt Rim](https://github.com/karlomikus/vue-salt-rim).

<p align="center">
    <a href="https://bar-api.karlomikus.com" target="_blank">Click here to view API demo.</a>
    <br>
    <a href="https://bar.karlomikus.com" target="_blank">Click here to view frontend demo.</a>
    <br>
    <strong>Email:</strong> admin@example.com &middot; <strong>Password:</strong> password
</p>

## Features
- Includes all current IBA cocktails
- Over 100 ingredients
- Endpoints for managing of ingredients and cocktails
- Mark ingredients you have and get all cocktails that you can make
- Detailed cocktail and ingredient information
- Ability to upload and assign images
- Shopping list for missing ingredients
- Automatic indexing of data in Meilisearch
- Cocktail ingredient substitutes
- Assign glass types to cocktails
- Cocktail recipe scraping

## Planned features
- Cocktail recipe sharing
- Cocktail and shopping list printing
- User defined cocktail collections
- Cocktail ratings
- Add user notes to cocktail
- Add cocktail flavor profiles

## Installation

This application is made with Laravel, so you should check out [deployment requirements](https://laravel.com/docs/9.x/deployment) for a standard Laravel project.

The basic requirements are:

- PHP >= 8.1
    - GD Extension
- Sqlite 3
- Working [Meilisearch server](https://github.com/meilisearch) instance (v0.29)
- (Optional) Redis server instance

## Docker setup

Docker setup is the easiest way to get started. This will run only the server but you can [checkout how to setup the whole Bar Assistant stack here.](https://github.com/bar-assistant/docker)

``` bash
$ docker volume create bass-volume

$ docker run -d \
    --name bar-assistant \
    -e APP_URL=http://localhost:8000 \
    -e MEILISEARCH_HOST=http://localhost:7700 \
    -e MEILISEARCH_KEY=masterKey \
    -e REDIS_HOST=redis \
    -v bass-volume:/var/www/cocktails/storage \
    -p 8000:80 \
    kmikus12/bar-assistant-server
```

Docker image exposes the `/var/www/cocktails/storage` volume, and there is currently and issue with host permissions if you are using a local folder mapping.

### Meilisearch

Bar Assistant is using Meilisearch as a primary [Scout driver](https://laravel.com/docs/9.x/scout). It's main purpose is to index cocktails and ingredients and power filtering and searching on the frontend. Checkout [this guide here](https://docs.meilisearch.com/learn/cookbooks/docker.html) on how to setup Meilisearch docker instance.

### Database file backup

You can copy the whole .sqlite file database with the following:

``` bash
# Via docker
$ docker cp bar-assistant:/var/www/cocktails/storage/database.sqlite /path/on/host

# Via docker compose
$ docker compose cp bar-assistant:/var/www/cocktails/storage/database.sqlite /path/on/host
```

### Database dump SQL

You can dump your database to .sql file using the following:

``` bash
# Via cli
$ sqlite3 /var/www/cocktails/storage/database.sqlite .dump > mydump.sql

# Via docker
$ docker exec bar-assistant sqlite3 /var/www/cocktails/storage/database.sqlite .dump > mydump.sql

# Via docker compose
$ docker compose exec bar-assistant sqlite3 /var/www/cocktails/storage/database.sqlite .dump > mydump.sql
```

## Manual setup

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
$ php artisan migrate --force

# To fill the database with data
$ php artisan bar:open

# Or with specific email and password
$ php artisan bar:open --email=my@email.com --pass=12345
```

## Usage

Checkout `/docs` route to see endpoints documentation.

Default login information is:
- email: `admin@example.com`
- password: `password`

## FAQ

### I'm missing images of cocktails and ingredients.

Check that you have correctly configured your docker volumes.

It can also mean that there are missing attributes in your indexes. You can run the following command to sync cocktails and ingredients to their indexes:

``` bash
# Docker compose commands:
# Sync cocktails
$ docker compose exec -it bar-assistant php artisan scout:import "Kami\\Cocktail\\Models\\Cocktail"
# Sync ingredients
$ docker compose exec -it bar-assistant php artisan scout:import "Kami\\Cocktail\\Models\\Ingredient"
```

## Contributing

TODO, Fork and create a pull request...

## License

The Bar Assistant API is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
