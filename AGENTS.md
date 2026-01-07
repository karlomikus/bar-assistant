# AGENTS.md

## Project information
Bar Assistant is all-in-one solution for managing your home bar. Compared to other recipe management software that usually tries to be more for general use, Bar Assistant is made specifically for managing cocktail recipes. This means that there are a lot of cocktail-oriented features, like ingredient substitutes, first-class ingredients, ABV calculations, unit switching and more.

## Quick Facts
- Stack: PHP 8.4, SQLite, Redis (cache)

## Folder structure
- Folder `app` contains the complete Laravel application
- Folder `src` is a new code that will follow Domain Driven Design principles
- Folder `tests` contains tests

## Architecture Guidelines
- New code should follow DDD and Hexagonal Architecture principles
- Tests will use `Tests\Infrastructure\InMemory*` implementations instead of mocks/stubs where applicable

## Code style
- Use PHP 8.4+ with strict types