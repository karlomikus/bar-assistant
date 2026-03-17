# Bar Assistant Write Workflow Reference

This document explains the project convention for implementing write-side behavior that starts in a Laravel controller and ends in an infrastructure repository adapter.

The clearest current example is the Ingredient flow:

- `app/Http/Controllers/IngredientController.php`
- `src/Application/Ingredient/IngredientService.php`
- `src/Domain/Ingredient/IngredientRepository.php`
- `app/Infrastructure/EloquentIngredientRepository.php`
- `app/Providers/InfrastructureServiceProvider.php`
- `tests/Unit/Application/Ingredient/IngredientServiceTest.php`

## Layer Map

| Layer | Purpose | Typical file |
|------|---------|--------------|
| HTTP controller | Authorization, request parsing, status code | `app/Http/Controllers/*Controller.php` |
| HTTP request / validator | Input validation | `app/Http/Requests/*Request.php` |
| Application DTO | Boundary object for the use case | `src/Application/*/DTO/*.php` |
| Application service | Use case orchestration | `src/Application/*/*Service.php` |
| Domain repository port | Persistence contract | `src/Domain/*/*Repository.php` |
| Infrastructure repository | Eloquent implementation and mapping | `app/Infrastructure/Eloquent*Repository.php` |
| DI registration | Interface-to-adapter binding | `app/Providers/InfrastructureServiceProvider.php` |
| Unit test | Service behavior with in-memory repos | `tests/Unit/Application/.../*Test.php` |

## End-to-End Workflow

### 1. Start from the use case

Decide the exact business operation first, not just the route shape.

Good examples in this repo:

- `createIngredient(...)`
- `updateIngredient(...)`
- `deleteIngredient(...)`

Prefer operation-oriented names over generic persistence names.

### 2. Define the application DTOs

The controller should not pass Laravel request objects into the application layer.

Instead:

- create a request DTO for each write use case
- create a result DTO when the controller needs a persisted ID or other response data

Ingredient examples:

- `src/Application/Ingredient/DTO/CreateIngredient.php`
- `src/Application/Ingredient/DTO/UpdateIngredientRequest.php`
- `src/Application/Ingredient/DTO/IngredientResult.php`

DTOs are the boundary between framework code and application code.

### 3. Implement the controller method

Controllers in this codebase usually do four things for writes:

1. validate the HTTP request
2. authorize the action
3. map request data into DTOs
4. call the application service and return an HTTP response

Ingredient create example:

- `IngredientController::store()`

Observed pattern:

- `IngredientRequest` handles request validation
- extra constraints can be checked with `Validator::make(...)`
- policy failures call `abort(403)`
- request data is translated into `CreateIngredient` and nested DTOs like `CreateIngredientPrice`
- the service is called with the DTO
- the response returns `201` and a `Location` header

Create skeleton:

```php
public function store(MyService $service, MyRequest $request): Response
{
    if ($request->user()->cannot('create', MyModel::class)) {
        abort(403);
    }

    $dto = new CreateMyEntity(
        barId: bar()->id,
        userId: $request->user()->id,
        name: $request->string('name')->toString(),
    );

    $result = $service->createMyEntity($dto);

    return new Response(status: 201, headers: [
        'Location' => route('my-entities.show', $result->id, false),
    ]);
}
```

### 4. Implement the application service method

The service should orchestrate domain logic, not behave like an Eloquent wrapper.

In Bar Assistant, write-side service methods usually:

- convert primitive DTO values into domain IDs and value objects
- create or load domain entities
- fetch related aggregates through repository ports
- throw `EntityNotFoundException` when a dependency is missing
- call domain behavior methods
- persist through the repository port
- return a result DTO when useful

Ingredient create flow:

- `IngredientService::createIngredient()`

Observed behavior:

- constructs `BarId`, `Name`, `UserId`, `Authors`, `RecordTimestamps`
- creates the aggregate through `Ingredient::create(...)`
- enriches it through helper methods such as `assignIngredientParts()` and `assignIngredientPrices()`
- loads parent ingredients through `IngredientRepository`
- throws `EntityNotFoundException` when a related entity is missing
- saves through `IngredientRepository::save(...)`
- returns `IngredientResult::fromIngredient(...)`

#### Create method checklist

- accept a dedicated create DTO
- instantiate the aggregate through a static factory or constructor pattern already used by the domain
- resolve related resources through repository ports
- translate missing dependencies into typed application exceptions
- persist once the aggregate is fully prepared
- return a result DTO if the controller needs the generated ID

#### Update method checklist

Ingredient example:

- `IngredientService::updateIngredient()`

Observed behavior:

- loads the aggregate via the repository port
- throws `EntityNotFoundException` if not found
- mutates the aggregate with domain methods like `updateDetails(...)`
- clears and rebuilds dependent collections when that matches current behavior
- persists the aggregate
- performs hierarchy operations after the main save when needed
- returns `IngredientResult`

Update skeleton:

```php
public function updateMyEntity(UpdateMyEntityRequest $request): MyEntityResult
{
    $entity = $this->repository->findById(new MyEntityId($request->entityId));
    if ($entity === null) {
        throw new EntityNotFoundException('The entity to update was not found');
    }

    $entity->updateDetails(
        name: Name::fromString($request->name),
        updatedBy: new UserId($request->userId),
    );

    $entity = $this->repository->save($entity);

    return MyEntityResult::fromEntity($entity);
}
```

#### Delete method checklist

Ingredient example:

- `IngredientService::deleteIngredient()`

Observed behavior:

- loads the aggregate first
- throws `EntityNotFoundException` if missing
- performs domain cleanup for dependent children
- calls the repository delete method with a domain ID

Delete skeleton:

```php
public function deleteMyEntity(int $entityId): void
{
    $id = new MyEntityId($entityId);
    $entity = $this->repository->findById($id);

    if ($entity === null) {
        throw new EntityNotFoundException('The entity to delete was not found');
    }

    $this->repository->delete($id);
}
```

### 5. Define or update the repository port

Repository interfaces in this codebase live in the domain layer because the application service depends on the abstraction, not the implementation.

Ingredient example:

- `src/Domain/Ingredient/IngredientRepository.php`

Key conventions:

- method names are behavior-oriented but still pragmatic: `findById`, `findMany`, `save`, `delete`
- parameters and returns use domain IDs and aggregates, not Eloquent models
- complex aggregate operations get explicit methods such as `saveHierarchyChanges(...)`

The repository port should describe what the application service needs, not mirror a database table.

### 6. Implement the infrastructure repository adapter

Repository adapters in this repo live under `app/Infrastructure` and usually use Eloquent models plus explicit mapping logic.

Ingredient example:

- `app/Infrastructure/EloquentIngredientRepository.php`

Responsibilities:

- load Eloquent models
- map models into domain aggregates
- map domain aggregates back into models
- handle database transactions for multi-step persistence
- assign generated IDs back to transient domain entities

Important conventions from `EloquentIngredientRepository::save()`:

- uses `findOrNew($aggregate->getId()?->value)` to handle create and update in one method
- opens a transaction with `DB::beginTransaction()`
- rolls back and rethrows on failure
- calls `setId()` after the initial save when the aggregate was transient
- persists dependent collections after saving the main row

This is the critical ID-assignment pattern:

```php
if ($ingredient->isTransient()) {
    $ingredient = $ingredient->setId(new IngredientId($ingredientModel->id));
}
```

That matches the project note that IDs come from the legacy database and are assigned after persistence.

### 7. Register the adapter in the container

Once the port and adapter exist, wire them in:

- `app/Providers/InfrastructureServiceProvider.php`

Pattern:

```php
$this->app->bind(MyRepository::class, EloquentMyRepository::class);
```

That keeps controllers and services unaware of the implementation class.

### 8. Test at the right layer

For application services, prefer unit tests using in-memory repositories from `tests/Infrastructure`.

Ingredient example:

- `tests/Unit/Application/Ingredient/IngredientServiceTest.php`

Observed test style:

- create in-memory repositories directly
- pass them to the service constructor
- assert result DTOs and thrown exceptions
- avoid mocking the domain behavior

Feature tests should cover controller behavior when needed:

- authorization
- validation response shape
- status codes and headers
- integration with Laravel routing

## Practical Guardrails

- Do not inject Eloquent repositories directly into controllers
- Do not pass Laravel request objects into the service layer
- Do not return Eloquent models from domain repositories
- Keep Eloquent mapping code in the adapter
- Use typed application exceptions instead of silent failures
- Preserve project conventions around strict types, named arguments, and value objects

## Files to Copy Patterns From

Use these files first when implementing a new workflow:

- `app/Http/Controllers/IngredientController.php`
- `src/Application/Ingredient/IngredientService.php`
- `src/Domain/Ingredient/IngredientRepository.php`
- `app/Infrastructure/EloquentIngredientRepository.php`
- `app/Providers/InfrastructureServiceProvider.php`
- `tests/Unit/Application/Ingredient/IngredientServiceTest.php`
