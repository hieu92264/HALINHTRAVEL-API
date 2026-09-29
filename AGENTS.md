# Repository Guidelines

## Project Structure & Module Organization

This Laravel 12 API is organized by domain in `app/Modules/<Domain>/` (for example, `Auth`, `Rental`, and `Dispatch`). Modules use `Controllers`, `Services`, `Interfaces`, `Models`, `Requests`, `Routes`, and local database files. Put shared enums and helpers in `app/Shared/`; configuration in `config/`; frontend files in `resources/`; and tests in `tests/Feature` or `tests/Unit`.

## Build, Test, and Development Commands

Develop the backend locally with Laragon (PHP, Composer, MySQL, and Redis), not Docker:

```powershell
composer install                       # install PHP dependencies
php artisan serve                       # run the backend API locally
composer test                           # clear config and run PHPUnit
vendor/bin/pint                         # format PHP
npm run build                           # produce Vite assets
```

Copy `.env.example` to `.env` and configure Laragon services before first use. Docker is only for exposing a running API to frontend developers for integration testing: use `docker compose up -d` when that handoff is explicitly needed. The Docker API is served at `http://localhost:8080`.

## Coding Style & Naming Conventions

Follow `.editorconfig`: UTF-8, LF, four spaces for PHP, and two for YAML. Format changed PHP with Laravel Pint. Use PSR-4 namespaces matching paths (for example, `App\Modules\Auth\Services\AuthService`), PascalCase classes, singular models, and `*Request`, `*Service`, and `*Controller` suffixes. Keep module routes in `Routes/index.php`; use Form Requests and thin controllers.

## Testing Guidelines

PHPUnit uses an in-memory SQLite database. Put HTTP/database behavior in `tests/Feature` and isolated logic in `tests/Unit`. Use descriptive `test_*` names, e.g. `test_admin_can_create_user`, and `RefreshDatabase` when needed. Run relevant tests during development and `composer test` before a PR. No coverage threshold is configured.

## Database Safety

Database data is protected. Never automatically run migrations, seeders, `migrate:fresh`, `migrate:refresh`, `db:wipe`, `docker compose down -v`, or any data-changing/destructive command. Do not alter production-like `.env` values. Create required migrations or seeders, provide the exact command, and wait for explicit approval before execution. This applies even during local setup; in-memory SQLite tests are the exception.

## Commit & Pull Request Guidelines

Recent commits use concise imperative subjects, e.g. `Implement JWT authentication...`. Keep commits focused. PRs should explain behavior and database/configuration impacts, link issues when available, and include request/response examples or screenshots for visible changes. Confirm formatting and tests pass; never commit `.env` secrets or runtime files.
