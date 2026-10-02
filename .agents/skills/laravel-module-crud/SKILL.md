---
name: laravel-module-crud
description: Build or review a conventional REST CRUD resource in this Laravel modular API, including typed request DTOs, permissions, service bindings, response contracts, and feature tests. Use for standard module resources; not bespoke workflow endpoints or schema-only work.
---

# Laravel Module CRUD

Build CRUD resources using the repository's module conventions while preserving the resource's existing API and business contract.

## Discover the contract first

Before editing, inspect the target module's routes, model, migrations, related services, request classes, service-provider bindings, permissions, response conventions, and tests. Determine and preserve the choices specific to that resource:

- whether the list is flat, filtered, or paginated;
- whether inactive records are visible and whether delete means deactivation or deletion;
- required relationships, authorization, and domain rules;
- fields exposed in the response.

Do not infer a new resource's policy from Customer alone. Follow the nearest established resource when it is compatible, otherwise ask for the missing product decision.

## Implement the request flow

Keep the flow as routes → Form Request → DTO → controller → service/interface → model. Controllers should delegate only; validation and input normalization belong in Form Requests, while persistence and business rules belong in services.

- Convert validated scalar enum values explicitly before constructing a typed DTO; validation rules do not cast them.
- Preserve fixed-point values such as money as validated decimal strings, not floats.
- For PATCH-like `PUT` updates, track fields present in validated input so omitted fields are not overwritten and an explicit `null` can clear a nullable field.
- Use model `$fillable`/`forceFill` deliberately. Keep generated IDs/codes server-owned when that is the contract.
- Map public response arrays explicitly when model serialization could expose future internal attributes or change the API shape.
- Register service-interface bindings in the application provider and apply the established auth and permission middleware in the module route file.

## Data and operational safety

Inspect model casts, foreign keys, indexes, and dependent relations before changing persistence behavior. Do not create, run, or alter migrations, seeders, or production-like environment data unless the user explicitly authorizes that operation. Use in-memory SQLite only through the test suite.

## Verify observable behavior

Add or update feature tests for authorization, validation, successful create/read/update behavior, list contract, and deletion/deactivation semantics. Include resource-specific edge cases such as enum transitions, null-clearing behavior, generated codes, and decimal precision when applicable. Set an explicit locale header when asserting localized validation text.

Format only changed PHP files with Pint, run targeted tests first, then run the full test suite. Report routes affected, contract decisions retained, and any checks that could not be run.
