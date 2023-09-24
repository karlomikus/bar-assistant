<p align="center">
    <a href="https://karlomikus.com" target="_blank"><img src="resources/art/readme-logo.png" alt="Bar assistant Logo" width="500"></a>
</p>

<p align="center">
    <a href="https://hub.docker.com/r/barassistant/server"><img src="https://img.shields.io/docker/v/barassistant/server?style=for-the-badge&sort=semver" alt="Docker image"></a>
    <img src="https://img.shields.io/github/license/karlomikus/bar-assistant?style=for-the-badge" alt="Docker image">
    <img src="https://img.shields.io/github/actions/workflow/status/karlomikus/bar-assistant/php.yml?branch=master&style=for-the-badge&label=Tests" alt="Tests">
</p>

## 🍸 Bar Assistant

Bar Assistant is a self hosted application for managing your home bar. It allows you to search and filter cocktails, add ingredients and create custom cocktail recipes.

This repository only contains the API server, if you are looking for easy to use web client, take a look at [Salt Rim](https://github.com/karlomikus/vue-salt-rim).

<p align="center">
    <a href="https://bar.karlomikus.com/bar/docs" target="_blank">Click here to view API demo.</a>
    <br>
    <a href="https://bar.karlomikus.com" target="_blank">Click here to view frontend demo.</a>
    <br>
    <strong>Email:</strong> admin@example.com &middot; <strong>Password:</strong> password
</p>

## Features
- Includes all current IBA cocktails
- Over 100 ingredients
- Add and manage multiple bars
- Fine grained control with user roles
- Endpoints for managing of ingredients and cocktails
- Mark ingredients you have and get all cocktails that you can make
- Detailed cocktail and ingredient information
- Ability to upload and assign images
- Shopping list for missing ingredients
- Automatic indexing of data with your prefered search engine
    - Supports all drivers that Laravel Scout supports: Algoila, Meilisearch, Database
- Cocktail ingredient substitutes
- Assign glass types to cocktails
- Cocktail recipe scraping
- Cocktail ratings
- User cocktail collections
- Cocktail notes

## Documentation

[Documentation is available here.](https://bar-assistant.github.io/docs/)

## Contributing

Feel free to create a pull request or open a issue with bugs/feature ideas.

## Development environment

You can use docker to quick start with the development.

1. Clone the repository
2. Copy `.env.dev` as `.env`, or setup your own env file
3. Run `docker compose up -d`
4. Then run the following commands:

``` bash
$ docker compose exec app composer install
$ docker compose exec app php artisan key:generate
$ docker compose exec app php artisan storage:link
$ docker compose exec app php artisan migrate
```

Xdebug vscode launch config:
```json
{
    "name": "Listen for Xdebug",
    "type": "php",
    "request": "launch",
    "port": 9003,
    "hostname": "host.docker.internal",
    "pathMappings": {
        "/var/www/cocktails/": "${workspaceFolder}"
    }
}
```

## License

The Bar Assistant API is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
