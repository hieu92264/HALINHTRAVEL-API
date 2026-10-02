# Database design review checklist

## Establish review context

Confirm the target database, backend/ORM, business workflows, current schema artifacts, representative queries, size/growth, and deployment constraints. If evidence is missing, report it as an assumption or open question, not a defect.

## Integrity and business fit

- Every SQL table has an appropriate key; MongoDB identity and document ownership are explicit.
- Relationships, cardinality, requiredness, uniqueness, delete behavior, and orphan handling match business rules.
- Values have appropriate types, precision, defaults, nullability, validation, and lifecycle/status rules.
- Financial and historical documents preserve the right snapshots and cannot be casually deleted or rewritten.
- Normalization is sufficient for transactional work; every denormalization, duplicate field, embedded document, or JSON field has a stated query or history reason.

## Query and operational fit

- Indexes support known filters, joins/lookups, sorting, grouping, uniqueness, and retention—not hypothetical columns.
- Composite index order and existing redundant/prefix indexes are reviewed with the target engine's planner or MongoDB `explain()`.
- Large-list pagination, reporting workload, write contention, document size, transactions, and retention are considered where relevant.
- The proposed ORM mappings retain database constraints and do not enable accidental cascades or schema synchronization in shared environments.
- Migration sequencing is compatible with existing data and currently deployed services.

## Finding format

Report material issues only, ordered by severity:

```markdown
### High — Invoice number is not unique per legal entity
**Problem:** `invoices.number` has no business uniqueness constraint.
**Why:** Concurrent application validation can create duplicate legal document numbers.
**Impact:** Reconciliation, reporting, and compliance ambiguity.
**Recommended change:** Confirm the numbering scope and add the matching composite unique constraint through a compatible migration.
```

Use `Critical` for credible data loss/corruption or security/compliance failure; `High` for likely integrity or major operational failures; `Medium` for meaningful maintainability/performance risk; and `Low` for bounded improvement. Do not label a convention preference as an issue without a concrete impact.
