# Schema design and platform profiles

## Choose the model from the workload

Start with transactional boundaries, consistency needs, reporting needs, data size, and how records are read and updated. A business system with customers, contracts, invoices, payments, and reporting normally starts with a relational SQL model. MongoDB is appropriate when aggregate documents are naturally owned and read together, fields vary materially, and cross-document integrity is not the dominant concern.

Do not choose MongoDB merely to avoid joins, or relational SQL merely from habit. State the decision and the rejected alternative when the choice is consequential.

## Relational model

Model an entity once, use foreign keys for real relationships, and reach third normal form before considering a documented denormalization. Separate master data from transaction data and model document headers and lines separately when line-level querying, reporting, or lifecycle matters.

Example for a booking workflow:

```text
customers 1 --- N bookings 1 --- N booking_items
bookings  1 --- N payments
```

`bookings.customer_id` is required and references `customers.id`. A payment should normally be restricted from deletion while historical booking or financial records exist. A booking invoice may snapshot customer name and billing address because the current customer row cannot reconstruct an issued document.

Use `DECIMAL`/`numeric` for money, a domain-appropriate precision for quantities and rates, and native date/time types. A status is a lifecycle with allowed transitions, not an arbitrary string. Favor an application enum plus a constrained string where values may evolve; use a lookup table only when values carry business metadata, permissions, ordering, or independent lifecycle.

## SQL engine notes

| Engine | Design considerations |
|---|---|
| MySQL | Respect the configured engine and collation; use `BIGINT UNSIGNED` only when it matches project convention. InnoDB foreign keys need compatible types and indexes. |
| PostgreSQL | Use `numeric` for exact values; use native `uuid`, `jsonb`, partial indexes, and enums only when their lifecycle and portability are justified. |
| SQL Server | Use `decimal`, `datetime2`, and a deliberate clustered-key strategy. Consider filtered indexes and include columns for demonstrated query plans. |

Use the target dialect's actual capabilities rather than copying syntax from another database. For every design, call out engine-dependent features such as check constraints, partial/filtered indexes, generated columns, and native enums.

## MongoDB model

Model around aggregate ownership and access patterns, not a relational table-for-table conversion. Embed bounded, owned data that is read and written with the parent. Reference independently owned, high-cardinality, frequently updated, or separately queried data. Remember the 16 MiB BSON document limit.

```json
{
  "_id": "booking_01",
  "customerId": "customer_42",
  "status": "confirmed",
  "items": [{ "serviceId": "svc_7", "quantity": 2, "unitPrice": "125.00" }],
  "billingSnapshot": { "name": "A. Nguyen", "address": "..." }
}
```

This embeds bounded booking items and a historical snapshot, but references the customer and service masters. Define validation, uniqueness, transactions where needed, ownership, deletion behavior, and consistency/repair strategy explicitly; MongoDB references do not create database-enforced foreign keys.

## ORM and backend boundary

Laravel/Eloquent, Prisma, TypeORM, and EF Core map an approved data model; they do not determine business cardinality. NestJS and Express are application hosts, so identify the actual ORM or driver. In .NET, identify EF Core versus direct SQL. Keep conceptual design independent, then present optional mapping-specific snippets only after the target has been confirmed and the user asks for implementation.
