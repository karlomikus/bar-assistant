# AGENTS.md

## Project Overview
Bar Assistant is a Laravel 12 API (PHP 8.4) for managing home bars and cocktail recipes.

- **Stack**: PHP 8.4, Laravel 12, SQLite, Redis (cache/sessions), Meilisearch (Scout search — required by the test suite), optional Laravel Horizon queue, OpenAPI 3.1.
- **Dev environment**: Docker Compose (`docker-compose.yml`). Prefix PHP/Composer commands with `docker compose exec app`.
- **Databases**: Local dev SQLite is at `./storage/bar-assistant/database.ba3.sqlite`; tests use SQLite `:memory:` (`phpunit.xml`).

## Working Rules
- **Tests**: Write new tests **only when explicitly asked**. Run existing targeted tests to verify changes.
- **Docs**: Do not create separate markdown documentation or reference guides unless asked.
- **Formatting**: Always run Pint on modified PHP files (`pint.json` uses `psr12` with imports sorted by **line length**, not alphabetically).

## Folder Structure & Namespaces
- `app/` (`Kami\Cocktail\`): Legacy Laravel application and infrastructure layer (controllers, FormRequests, Eloquent models, OpenAPI attributes, Eloquent adapters in `app/Infrastructure/`).
- `src/` (`BarAssistant\`): DDD / Hexagonal Architecture layer (`src/Domain/` and `src/Application/`).
- `tests/` (`Tests\`):
  - `tests/Unit`: Pure unit tests (domain & application logic, using `InMemory*` doubles).
  - `tests/Integration`: Repository (`Eloquent*`), domain event subscriber, and external import/export tests.
  - `tests/Feature`: Laravel HTTP endpoint tests.
  - `tests/Scrapers`: Recipe scraper tests.
  - `tests/Infrastructure`: `InMemory*` repository implementations for unit tests.

## Key Architecture Wiring & Gotchas
- **Repository bindings**: Domain ports live in `src/Domain/...`; Eloquent adapters live in `app/Infrastructure/Eloquent*Repository.php` and must be bound in `app/Providers/InfrastructureServiceProvider.php`.
- **Domain events**: Dispatched via `BarAssistant\Domain\DomainEventDispatcher::instance()`. Every event class must have a `#[DomainEventName(...)]` attribute. Subscribers live in `app/Infrastructure/DomainEventSubscriber/` and are registered in `app/Providers/AppServiceProvider::boot()`. Call `DomainEventDispatcher::reset()` in unit tests that register/dispatch events.
- **Bar tenant context**: Bar-scoped routes use `EnsureRequestHasBarQuery` middleware, which reads the **`Bar-Assistant-Bar-Id` HTTP header** (not a query param) and binds `BarContext` (accessible via the `bar()` helper in `app/helpers.php`).
- **PHPStan scope**: `phpstan.neon.dist` runs at `level: max` on **`src/` only**. Pass explicit file paths to analyze changes in `app/`.
- **OpenAPI**: `composer openapi` scans `app/` only and writes `docs/openapi-generated.yaml` (linted by Spectral in CI). When API contracts change, also sync frontend types via `../scripts/generate-api-client.sh`.
- **Image processing (`jcupitt/vips`)**: Requires system package `libvips42`, PHP FFI (`ffi.enable=true`), and `zend.max_allowed_stack_size=-1` (already configured in Docker/CI; missing these causes silent failures or crashes in image tests).

## Commands

```bash
# Tests (CI-preferred Laravel wrapper; requires meilisearch service)
docker compose exec app composer test

# Targeted PHPUnit runs
docker compose exec app vendor/bin/phpunit tests/Unit/UtilsTest.php
docker compose exec app vendor/bin/phpunit --filter=testCalculateAbv
docker compose exec app vendor/bin/phpunit --testsuite=Unit

# Static analysis (PHPStan level: max — defaults to src/ only)
docker compose exec app vendor/bin/phpstan analyse
docker compose exec app vendor/bin/phpstan analyse src/Domain/Cocktail

# Code style (Pint — psr12, length-sorted imports)
docker compose exec app vendor/bin/pint
docker compose exec app vendor/bin/pint --test

# Automated refactoring preview (Rector)
docker compose exec app vendor/bin/rector process --dry-run

# Regenerate OpenAPI spec (scans app/ -> docs/openapi-generated.yaml)
docker compose exec app composer openapi

# Inspect local dev SQLite DB
docker compose exec app sqlite3 ./storage/bar-assistant/database.ba3.sqlite ".schema"
docker compose exec app sqlite3 ./storage/bar-assistant/database.ba3.sqlite "SELECT * FROM table_name LIMIT 10;"
```

## Conventions

### General PHP
- Use `declare(strict_types=1);` and explicit parameter/return types in all new and modified files.
- Use named parameters in method calls for clarity: `createCocktail(barId: $id, name: $name)`.
- Use PHPDoc generic annotations for arrays (e.g., `@param CocktailIngredient[] $ingredients` or `array<int, T>` as needed for PHPStan max).
- Write comments only to explain non-obvious business logic.

### `src/` (DDD / Hexagonal)
- Follow `UbiquitousLanguage.md` for domain terminology.
- **Aggregate roots**: `final` classes implementing `Identity`, with private constructors, static factory methods, fluent setters (`return $this`) where appropriate, and invariants validated in constructors/setters.
- **IDs**: Legacy auto-incrementing integer IDs are assigned post-persistence via `setId()` (checked via `isTransient()`).
- **Value objects**: `final readonly` classes with an `equals()` method.
- **Application services**: `final readonly` services accepting and returning `readonly` DTOs.
- **Unit testing**: Prefer `Tests\Infrastructure\InMemory*` doubles over mocks/stubs so `tests/Unit` stays isolated from Eloquent/DB.

## CI Pipeline (`.github/workflows/php.yml`)
1. Install `libvips42`, PHP 8.4 (`bcmath`, `intl`, `ffi`), and start Meilisearch
2. `composer install`
3. `./vendor/bin/pint --test`
4. `composer test`
5. `vendor/bin/phpstan analyse`
6. Spectral lint on `docs/*.yaml`
