## Contributing

Contributions Welcome! You can contribute in the following ways:

- Open an issue to suggest a new feature
- Open an issue to report a bug
- Fork and create a PRs

## PRs

Make sure your code changes pass the following:

- All tests pass: `vendor/bin/phpunit`
- PHPStan analysis pass: `vendor/bin/phpstan analyse`
- Codestyle pass: `vendor/bin/ecs check --fix --clear-cache`

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

5. (Optional) Add bar data
``` bash
$ git clone https://github.com/bar-assistant/data.git resources/data
```

Example Xdebug vscode launch config:
```json
{
    "name": "Listen for Xdebug",
    "type": "php",
    "request": "launch",
    "port": 9003,
    "hostname": "localhost",
    "pathMappings": {
        "/var/www/cocktails/": "${workspaceFolder}"
    }
}
```