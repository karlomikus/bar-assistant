<p align="center">
    <a href="https://karlomikus.com" target="_blank"><img src="resources/art/logotype.svg" alt="Bar assistant Logo" width="300"></a>
</p>

## 🍸 Bar assistant

Bar assistant is a self hosted application for managing your home bar. It allows you to add ingredients and create custom cocktail recipes.

This repository only contains the API server, if you want easy to use client, take a look at [Salt Rim](https://github.com/karlomikus/vue-salt-rim).

## Features

- Includes all current IBA cocktails
- Over 100 ingredients
- Endpoints for managing of ingredients and cocktails
- Mark ingredients you have and get all cocktails that you can make
- Detailed cocktail and ingredient information
- Ability to upload and assign images
- Shopping list for missing ingredients
- Automatic indexing of data in Meilisearch

## Planned features

- Cocktail recipe sharing
- Finish multi user features
- User defined cocktail collections
- Cocktail ratings
- Add user notes to cocktail
- Add cocktail flavor profiles
- Ingredient and cocktail aliasing
- Ingredient substitutes

## Installation

This application is made with Laravel, so you should [follow installation instructions](https://laravel.com/docs/9.x/deployment) for a standard Laravel project.

### Requirements:

- PHP >=8.1
- Sqlite 3
- Working [Meilisearch server](https://github.com/meilisearch)

After initial Laravel setup do the following:

1. Update your environment variables

```
# Your application instance URL
APP_URL=

# Meilisearch instance URL
MEILISEARCH_HOST=

# Meilisearch search key
MEILISEARCH_KEY=
```

2. Run the commands
```
# To setup the database:
$ php artisan migrate

# To setup correct image paths
$ php artisan storage:link

# To fill the database with data
$ php artisan bar:open
```

Default login information is:

- email: `admin@example.com`
- password: `password`

## Docker

[Docker image](https://hub.docker.com/r/kmikus12/bar-assistant-server).

[Also checkout how to setup the whole Bar Assistant stack here.](https://github.com/karlomikus/vue-salt-rim#docker-compose)

## Contributing

TODO

## License

The Bar Assistant API is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
