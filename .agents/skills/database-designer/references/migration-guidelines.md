# Safe schema evolution

## Classify the change

Mark each proposed change as additive, backward-compatible, breaking, data migration/backfill, constraint/index change, or destructive retirement. Include deployment order, compatibility window, validation, rollback/forward-fix plan, locks and runtime risk, plus ownership of any backfill.

Do not run migrations, backfills, seeders, or destructive operations without explicit authorization. Never recommend dropping a populated column/table as a first release step.

## Compatible sequence

For a required new field on a populated table: add it in a compatible form; deploy code that tolerates old rows and writes new values; backfill using a controlled process; validate; then enforce the constraint in a later compatible release. A rename normally follows add, dual read/write where necessary, backfill, cutover, and only then retirement.

Build indexes using the least disruptive method supported by the confirmed engine and version. Constraint validation, lock behavior, and transactional DDL differ between MySQL, PostgreSQL, and SQL Server, so do not provide a one-size-fits-all command.

## Tooling mapping

- Laravel: generate migrations only on request; separate schema migrations from domain/data backfills and review generated SQL.
- Prisma: review migration SQL, shadow-database assumptions, and deployment order; do not treat `db push` as a production migration strategy.
- TypeORM: prefer reviewed explicit migrations over uncontrolled synchronize behavior for shared or production data.
- EF Core: generate and review migrations, then plan SQL Server/PostgreSQL provider specifics and deployment bundles/scripts as appropriate.
- Raw SQL: include reversible or forward-fix guidance according to engine capabilities; do not claim every destructive change has a safe down migration.
- MongoDB: version document shape, make readers tolerate old/new forms, use idempotent batched migrations, validate progress, and keep repair/rollback strategy explicit.

## Required migration output

State prerequisites, affected data, compatibility with currently deployed applications, expected operational risk, test path, and exact confirmation required before execution. Keep application implementation outside scope unless specifically requested.
