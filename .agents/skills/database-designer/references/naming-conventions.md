# Naming conventions

## Adopt before inventing

Inspect and preserve the project's established database and framework conventions. A cross-cutting rename is a migration project, not a cosmetic improvement. Where no convention exists, choose one consistent convention and document it.

For Laravel/MySQL-oriented projects, the normal baseline is plural `snake_case` tables, singular model names, `id` primary keys, `customer_id` foreign keys, `is_active` booleans, and semantic timestamps such as `approved_at`. Avoid vague names such as `data`, `value`, `info`, `status1`, or `type2` unless the domain genuinely defines them.

## Portable choices

- Name tables/collections after domain concepts and relation columns after their target role.
- Use names that distinguish current state from historical snapshots: `billing_address` is clearer than `address` on an invoice.
- Name indexes and constraints predictably where the engine/framework does not do so safely: `uq_customers_tax_code`, `ix_bookings_customer_status_created_at`, `fk_bookings_customer`.
- Use a term consistently across API, ORM, and storage unless a framework mapping requires a deliberate translation.

## Platform profiles

| Profile | Default mapping guidance |
|---|---|
| Laravel / Eloquent | `snake_case` plural tables and conventional FK names minimize overrides; retain model casts/enums separately from DB type decisions. |
| Prisma | Choose application-facing model names freely and use `@map`/`@@map` when the database convention differs; avoid gratuitous remapping. |
| TypeORM / NestJS | Establish a naming strategy once; explicitly name columns/tables when interoperability or existing schema requires it. |
| EF Core / .NET | Respect existing PascalCase CLR and configured database naming conventions; configure mappings centrally rather than allowing accidental drift. |
| MongoDB / Express | Choose `camelCase` or `snake_case` document fields based on the existing API/storage convention; do not mix them within a collection. |

Names must reveal domain meaning, not framework implementation details. Do not create generic junction names or new duplicate entities merely because an ORM can generate them.
