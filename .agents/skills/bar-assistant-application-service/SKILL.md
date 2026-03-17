---
name: bar-assistant-application-service
description: Use when implementing or refactoring Bar Assistant write-side workflows that go from Laravel controllers to application services and Eloquent-backed infrastructure repositories. Covers controller orchestration, DTO mapping, repository ports/adapters, dependency injection, and create/update/delete service conventions used in this codebase.
---

# Bar Assistant Application Service Workflow

Project-specific guidance for implementing write-side behavior in Bar Assistant without bypassing the application layer.

## When to Use

Use this skill when you are:

- Adding a new controller action that should call a service in `src/Application`
- Implementing a new create, update, or delete use case
- Introducing a new domain repository port and Eloquent repository adapter
- Refactoring legacy controller logic into application services
- Wiring a new repository binding in `InfrastructureServiceProvider`

## Core Rule

For write operations, controllers should authorize and validate the request, build an application DTO, call an application service, and return the HTTP response. They should **not** contain persistence logic or call infrastructure repositories directly.

```
Controller -> Application Service -> Domain Repository port -> Eloquent adapter
```

## Best Example in This Repo

Use the Ingredient flow as the main reference:

- Controller: `app/Http/Controllers/IngredientController.php`
- Application service: `src/Application/Ingredient/IngredientService.php`
- Repository port: `src/Domain/Ingredient/IngredientRepository.php`
- Repository adapter: `app/Infrastructure/EloquentIngredientRepository.php`
- DI bindings: `app/Providers/InfrastructureServiceProvider.php`
- Unit tests: `tests/Unit/Application/Ingredient/IngredientServiceTest.php`

## Recommended Implementation Order

1. Define or update the domain repository port in `src/Domain/...`
2. Add request/result DTOs in `src/Application/.../DTO`
3. Implement the application service method in `src/Application/...Service.php`
4. Implement or extend the Eloquent repository adapter in `app/Infrastructure`
5. Wire the repository interface to the adapter in `InfrastructureServiceProvider`
6. Update the Laravel controller to authorize, map request data, and call the service
7. Add or update unit tests for the application service
8. Add or update feature tests for the controller behavior when needed

## Controller Responsibilities

- Resolve the application service from the container
- Perform policy checks with the authenticated user
- Validate request payloads with `FormRequest` and additional validators if needed
- Translate HTTP request data into application DTOs
- Return the correct HTTP status and headers

Controllers in this repo commonly:

- Call `abort(403)` for failed policy checks
- Build DTOs explicitly instead of passing Laravel requests into the application layer
- Return `201` with a `Location` header for create
- Return `204` for update and delete

## Application Service Responsibilities

Application services in this repo:

- Orchestrate use cases using domain entities and repository ports
- Convert primitive request data into domain value objects and IDs
- Throw typed application exceptions such as `EntityNotFoundException`
- Return result DTOs for create/update operations when the caller needs persisted identifiers or metadata
- Use repository methods for persistence instead of touching Eloquent directly

## Repository Conventions

- Repository interfaces live in `src/Domain/...`
- Eloquent adapters live in `app/Infrastructure`
- Adapter names follow `Eloquent{Name}Repository`
- Persisted IDs are database-generated and assigned back to transient domain objects with `setId()`
- Multi-step persistence is wrapped in DB transactions inside the adapter
- Mapping between Eloquent models and domain entities stays in the adapter

## Create / Update / Delete Expectations

### Create

- Controller validates input and authorizes `create`
- Controller builds a create DTO and calls the service
- Service builds a domain entity via a factory like `Entity::create(...)`
- Service resolves related entities through repository ports as needed
- Repository adapter persists the entity, assigns the generated ID, and returns the updated aggregate
- Controller returns `201` and `Location`

### Update

- Controller loads the legacy model when needed for authorization context
- Controller authorizes `edit`
- Controller builds an update DTO and calls the service
- Service loads the aggregate through the repository port
- Service throws `EntityNotFoundException` if the aggregate does not exist
- Service updates the aggregate through domain methods and persists it
- Controller returns `204` unless a response body is required

### Delete

- Controller loads the legacy model when needed for authorization context
- Controller authorizes `delete`
- Controller calls the delete service method with the route ID
- Service loads the aggregate or throws `EntityNotFoundException`
- Service performs any domain cleanup before deletion
- Repository adapter deletes the aggregate by domain ID
- Controller returns `204`

## Bar Assistant-Specific Details

- Domain code in `src/` uses strict typing and value objects heavily
- Legacy Laravel models still appear in controllers for policy checks and route-context lookups
- Services should stay framework-agnostic even if controllers are not
- Unit tests prefer in-memory repositories from `tests/Infrastructure` instead of mocks
- Complex write flows often need both a primary repository and supporting repositories, as shown by `IngredientService`

## Reference Documentation

- [references/IMPLEMENTATION-WORKFLOW.md](references/IMPLEMENTATION-WORKFLOW.md) — step-by-step controller/service/repository workflow for create, update, and delete

## Source Files to Study First

- `app/Http/Controllers/IngredientController.php`
- `src/Application/Ingredient/IngredientService.php`
- `src/Domain/Ingredient/IngredientRepository.php`
- `app/Infrastructure/EloquentIngredientRepository.php`
- `app/Providers/InfrastructureServiceProvider.php`
- `tests/Unit/Application/Ingredient/IngredientServiceTest.php`
