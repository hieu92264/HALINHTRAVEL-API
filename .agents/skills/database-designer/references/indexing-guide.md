# Query-led indexing guide

## Start with evidence

Collect representative reads and writes: filters, joins/lookups, sort order, grouping, pagination, selectivity, and expected volume. Name the query each proposed index supports and weigh its read benefit against write, storage, and maintenance cost. Inspect existing indexes first to avoid a prefix-equivalent or duplicate index.

For a common query:

```sql
SELECT id, status, created_at
FROM bookings
WHERE customer_id = ? AND status = ?
ORDER BY created_at DESC;
```

an index beginning with `(customer_id, status, created_at)` may be appropriate after checking selectivity and the database planner. It does not automatically make `WHERE status = ?` efficient: SQL composite indexes follow leftmost-prefix rules. Do not apply a universal “most selective first” slogan; equality predicates, range predicates, standalone use, and sort support all matter.

## Relational checks

- Primary and unique constraints generally create indexes. Confirm the target engine's behavior before adding duplicates.
- Index foreign-key columns when joins, reference checks, or deletion checks need them; SQL Server and PostgreSQL do not always create these indexes automatically.
- Consider covering/include columns, partial indexes (PostgreSQL), filtered indexes (SQL Server), full-text, or spatial indexes only for proven query needs.
- Use `EXPLAIN`/actual plans and production-like volumes before declaring an index necessary or harmful.

## MongoDB checks

Create indexes for actual filters, sort patterns, uniqueness, geospatial, text/search, or TTL retention requirements. Compound index order determines supported query prefixes and sort behavior. Multikey indexes on arrays, index intersection, partial indexes, sparse indexes, and unique indexes have semantics that must match the document shape.

Example:

```javascript
db.bookings.createIndex({ customerId: 1, status: 1, createdAt: -1 })
```

Use `explain()` and index usage metrics. Avoid unbounded embedded arrays merely to make one query convenient; they can undermine document size and index performance.

## Review failures

Flag missing support for an established high-cost query, a redundant index, incompatible index order, low-selectivity standalone indexes, write-heavy over-indexing, and uniqueness that is enforced only in application code when a database guarantee is needed.
