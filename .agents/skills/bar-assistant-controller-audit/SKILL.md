---
name: Bar Assistant Controller Audit
description: Audit and fix a Bar Assistant Laravel controller's request validation, responses, OpenAPI contract, route wiring, policy authorization, tenant scoping, and status codes.
metadata:
  opencode/autoinvoke: false
---

# Bar Assistant Controller Audit

Use this workflow when explicitly asked to audit, harden, or align a Bar Assistant HTTP controller with its routes, policies, validation, services, and OpenAPI contract.

## Scope

Start with the named controller, then inspect only the related code needed to understand and verify it:

- Form requests and request-to-DTO schema classes
- JSON resources and OpenAPI schemas
- Routes and route middleware
- Policies, user membership checks, and bar-context middleware
- Models/query filters used by read actions
- Application services, DTOs, and relevant domain invariants
- Existing controller feature tests

Read the project's `AGENTS.md` instructions before changing files. Treat unrelated changes as user work and do not overwrite them.

## Workflow

### 1. Establish the actual contract

Map every controller action into a small checklist containing:

- HTTP method and route
- Authentication, ability, and bar-context middleware
- Input source and FormRequest
- Authorization policy
- Application service or query called
- Successful status code and response shape
- Expected error responses

Run the route inventory to verify the attributes match reality:

```bash
docker compose exec app php artisan route:list --path=<controller-resource> -v
```

### 2. Audit and correct inputs

For every body-accepting action:

- Confirm the action uses the appropriate FormRequest.
- Validate types, required/nullable behavior, size limits, ranges, and nested fields.
- Keep validation aligned with database constraints and domain value objects.
- Add rules for fields consumed by the controller or DTO mapper, including nullable optional fields.
- Convert validated request values explicitly into typed DTO/schema properties.
- Do not rely on unchecked `mixed` values from `$request->input()`.

For updates, ensure validation does not accidentally permit changing ownership or bar identity. Validate referenced IDs against the resource's bar where applicable.

### 3. Audit authorization and tenant isolation

For every action:

- Confirm the model policy is discoverable and checks the intended ability.
- Guard nullable authenticated users even when authentication middleware normally prevents them.
- Authorize before calling write-side services or exposing resource data.
- Ensure create authorization uses the current bar context.
- Ensure show/update/delete policy checks use the loaded model's bar.
- Ensure list queries explicitly scope to `bar()->id`.
- Check that route IDs cannot bypass bar scoping or policy checks.
- Keep Eloquent models in controllers only for policy context and approved legacy queries; delegate writes to application services.

Be careful not to convert an expected `403` into a cross-bar data leak. Preserve the project's existing not-found/forbidden policy behavior unless a requirement says otherwise.

### 4. Audit outputs and status codes

Verify the implementation and OpenAPI contract agree:

- List and show return `200` with the correct resource/data wrapper.
- Create returns `201` with a relative `Location` header for the show route and normally no response body.
- Full update and delete return `204` with no content.
- Do not document a body for an action that returns none.
- Include every field actually emitted by the resource in its OpenAPI schema.
- Mark resource fields nullable when runtime output can be `null`.
- Keep schema types and constraints aligned with request validation and domain/database constraints.

### 5. Audit OpenAPI attributes

For each action, confirm:

- Operation method/path matches the Laravel route.
- Route parameters are required and correctly typed.
- Bar headers appear only where middleware requires bar context.
- Request bodies reference the correct schema and are marked required where appropriate.
- `200`, `201`, or `204` success responses match runtime behavior.
- `403` is documented for policy or bar-membership failures.
- `404` is documented when `findOrFail` or equivalent can fail.
- `422` is documented for FormRequest validation failures.
- Additional `400` or other responses are documented only when the action can actually return them.
- Location headers use an appropriate URI/reference format.

Prefer the project's response attributes (`BAO\SuccessfulResponse`, `BAO\NotAuthorizedResponse`, `BAO\NotFoundResponse`, `BAO\ValidationFailedResponse`) for consistent shared headers and schemas.

Do not change a shared OpenAPI attribute solely for one controller. Shared parameter changes can affect every generated client operation.

## Verification

Run focused checks before broad checks:

```bash
docker compose exec app vendor/bin/pint --test <changed-php-files>
docker compose exec app vendor/bin/phpstan analyse <changed-php-files>
docker compose exec app vendor/bin/phpunit <existing-targeted-test-file>
docker compose exec app composer openapi
```

Do not add tests unless the user explicitly asks for them. Run existing targeted tests when available, and report missing coverage as a follow-up rather than silently expanding the scope.

Finish with `git diff --check` and review both handwritten and generated diffs for unintended changes.

## Completion checklist

Before reporting completion, confirm:

- Every action has the correct input validation and policy check.
- No cross-bar read or write path is exposed.
- Runtime responses, status codes, and OpenAPI responses agree.
- Request and resource schemas match actual input/output fields.
- Routes and middleware match the documented bar-context requirements.
- Generated backend and frontend API artifacts are synchronized.
- Focused style, static analysis, tests, OpenAPI generation, and frontend type checks pass, with any pre-existing failures clearly reported.
