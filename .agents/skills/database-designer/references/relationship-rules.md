# Relationship, lifecycle, and history rules

## Describe every relationship

For each relationship state: parent, child, cardinality, ownership, required/optional reference, key or reference field, uniqueness, delete behavior, and historical consequences. Derive these from business actions and accountability, never from names alone.

| Pattern | Relational design | Key question |
|---|---|---|
| 1:1 | FK with `UNIQUE`, placed on the dependent/optional side | Can either record exist independently? |
| 1:N | Required or nullable child FK | Who owns creation and deletion of the child? |
| N:N | Junction table | Does the association have data or a lifecycle? |
| Self-reference | Nullable/required FK plus cycle rules | Can an entity manage itself or form loops? |

For example, `vehicle_driver` becomes a first-class assignment entity when it has `assigned_at`, role, status, or history. It is not a throwaway pivot table then.

## Delete and integrity

Use `CASCADE` only when a child is inseparable from its parent and removal is safe. Prefer `RESTRICT`/`NO ACTION` for contracts, invoices, payments, dispatch records, and other historical business facts. Use `SET NULL` only for a genuinely optional relationship whose meaning survives loss of the target. Define how application validation complements database constraints; it must not replace them.

MongoDB requires equivalent application or transactional enforcement. Document a repair or archival rule for references and choose transactions only for a concrete consistency boundary.

## History and audit

Ask whether an old business document must show values at the time it was issued. If yes, snapshot the legally or operationally relevant fields rather than relying only on the current master record. Do not add every audit field or soft delete universally; select fields from the actual restore, accountability, and retention requirements.

Distinguish `unknown`, `not applicable`, and `not yet entered` when they alter reporting or workflow. A nullable foreign key may be correct; a nullable required amount is normally a modeling failure.

## ORM mappings

Keep database constraints authoritative, then map them idiomatically:

- Laravel: use `foreignId()->constrained()` with explicit `restrictOnDelete`, `nullOnDelete`, or `cascadeOnDelete`; map pivot data as its own model when it has domain behavior.
- Prisma: express relation fields and `@relation` referential actions, but review the generated migration and database-specific constraint behavior.
- TypeORM: use explicit owning-side relations, join columns, and `onDelete`; do not enable cascading persistence/deletion as a substitute for lifecycle design.
- EF Core: configure requiredness, indexes, and `DeleteBehavior` explicitly, especially where SQL Server cascade-path limits or domain retention apply.

For MongoDB drivers or ODMs, make reference IDs, validation, and aggregate update rules explicit; ORM-style relations do not create foreign key enforcement.
