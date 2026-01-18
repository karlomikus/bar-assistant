# AGENTS.md

## Project information
Bar Assistant is all-in-one solution for managing your home bar. Compared to other recipe management software that usually tries to be more for general use, Bar Assistant is made specifically for managing cocktail recipes. This means that there are a lot of cocktail-oriented features, like ingredient substitutes, first-class ingredients, ABV calculations, unit switching and more.

## Quick Facts
- Stack: PHP 8.4, SQLite, Redis (cache)
- Development environment is run via docker compose stack
    - Service `app` runs the API
    - This means you need to prefix php commands with `docker compose exec app`

## Folder structure
- Folder `app` contains the complete Laravel application
- Folder `src` is a new code that will follow Domain Driven Design principles
- Folder `tests` contains tests
    - Unit tests are located in `tests/Unit`
    - Feature tests (laravel HTTP testing) are located in `tests/Feature`

## Architecture Guidelines
- New code should follow DDD and Hexagonal Architecture principles
- Tests will use `Tests\Infrastructure\InMemory*` implementations instead of mocks/stubs where applicable
- Changes in the `src` should only affect `Unit` testsuite
- Prefer static factory methods on aggregate roots with private constructors
- Do not write comprehensive documentation or reference guide in seperate markdown files

## Code style
- Use PHP 8.4+ with strict types
- Write code comments only to explain more complex business logic
