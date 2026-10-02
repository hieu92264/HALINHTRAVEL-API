# Planning workflow

Use this workflow proportionately. A small UI-only request may have no data or backend impact; state that conclusion and its basis instead of manufacturing work.

## 1. Understand the request

Capture:

- feature goal and the problem it solves;
- actors and their intended outcome;
- inputs, outputs, and success conditions;
- the main business workflow, including triggers and outcomes;
- confirmed scope, exclusions, and unresolved points.

Separate requirement statements into confirmed requirements, assumptions, recommendations, and open questions. Do not turn a recommendation into scope.

## 2. Inspect the existing system

When a project is supplied, inspect the relevant parts before choosing an approach:

- module/domain boundaries and naming;
- database schema, migrations, models, enums, and relationships;
- routes, controllers, requests, DTOs, services, repositories/query layers, resources, jobs, events, and tests;
- frontend routes, pages, components, composables, API clients, types, queries, stores, permissions, and tests;
- shared libraries and project conventions.

Report reusable capabilities and any functionality that already addresses part of the request. Prefer extending them over creating duplicates. Cite paths or named artifacts when known; never present a guessed file as an existing fact.

## 3. Analyze business behavior

Identify actors, use cases, preconditions, postconditions, business rules, permission boundaries, validation, failures, and edge cases.

For a status workflow, define allowed states and transitions, then list prohibited transitions and their error behavior. For example:

```text
draft -> confirmed -> in_progress -> completed
```

Only model states that the requirement or system evidence supports.

## 4. Determine data impact

Describe the requirements to hand off to a database-focused workflow:

- new or changed entities/tables/columns;
- relationships and lifecycle ownership;
- enums/statuses;
- uniqueness, foreign-key, and indexing needs;
- audit history or soft deletion only if justified;
- data migration, compatibility, or backfill considerations.

This is an impact assessment, not a full schema design. Mark all uncertain data decisions as assumptions or open questions.

## 5. Determine backend impact

For Laravel applications, determine which existing or new routes, controllers, Form Requests, DTOs, services, repository/query layers, models, enums, policies/permissions, API Resources, migrations, jobs/events/listeners, and tests are needed.

Prefer the repository's conventions. If no contrary convention exists, keep controllers thin; put request validation in Form Requests and business logic in services. Use jobs, events, or listeners only when asynchronous or decoupled behavior is actually needed.

## 6. Determine frontend impact

For Vue 3 applications, identify routes, pages, components, forms, dialogs, tables, API services, TypeScript types, queries, mutations, permission checks, and loading, empty, and error states.

Use the state boundary appropriate to the application:

```text
Server state             -> TanStack Vue Query
Application/global UI    -> Pinia
Component-local state    -> ref/reactive
```

Do not recommend Pinia merely to cache server state when TanStack Query meets the need.

## 7. Define the API contract

For each needed endpoint, state the method and path, purpose, request fields, response shape at a useful level, permissions, validation/business failures, and relevant status codes. Keep it a contract, not an implementation.

## 8. Define testing and rollout considerations

Cover happy paths, validation, authorization, not-found behavior, conflicts, business rules, state transition errors, and high-risk edge cases. Note integration, compatibility, migration, observability, or rollout needs only when evidence suggests them.

## 9. Sequence implementation

Order work by real dependencies, commonly: data definition, backend domain/API, backend tests, frontend types/services, queries/mutations, UI, permissions, integration tests, review. This is illustrative, not mandatory. Every step must name its goal, impacted files or modules, dependencies, acceptance criteria, and explicit handoff when another specialty owns the work.
