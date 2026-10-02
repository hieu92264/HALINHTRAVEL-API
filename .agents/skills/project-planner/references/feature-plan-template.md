# Feature plan template

Use this outline. Omit only sections that genuinely do not apply, and record the no-impact conclusion where it aids handoff.

```markdown
# Feature Plan: <feature name>

## 1. Goal

- Problem and desired outcome:
- Confirmed requirements:
- Success conditions:
- Scope exclusions:

## 2. Existing System Impact

- Relevant modules/artifacts inspected:
- Existing conventions and reusable capabilities:
- Gaps or conflicts:

## 3. Actors

| Actor/role | Goal | Authority |
| --- | --- | --- |

## 4. Use Cases

### <use case name>

- Trigger:
- Preconditions:
- Main flow:
- Alternate/error flows:
- Outcome:

## 5. Business Rules

- <checkable rule and intended enforcement boundary>

## 6. Workflow

Describe the business flow and, where applicable, state transitions and invalid transitions.

## 7. Data Requirements

- New/changed entities and relationships:
- Constraints/indexes/enums:
- Migration or compatibility considerations:
- Database handoff:

## 8. Backend Changes

- Modules and existing artifacts to extend:
- New artifacts needed (only after reuse check):
- Route/controller/request/service/query/model/resource/permission/test impact:

## 9. API Contract

### `METHOD /path`

- Purpose:
- Request:
- Response:
- Permissions:
- Possible errors:

## 10. Frontend Changes

- Routes/pages/components:
- API types and service:
- Queries, mutations, and state ownership:
- Permission, loading, empty, and error states:

## 11. Permissions

- Role/ability matrix and enforcement points:

## 12. Validation

- Client-side, request-level, business-rule, and database-level checks:

## 13. Edge Cases

- <scenario and expected behavior>

## 14. Testing Strategy

- Happy path:
- Validation and authorization:
- Not found/conflict/business rules:
- Edge cases and integration coverage:

## 15. Implementation Plan

### Step 1 — <name>

- Goal:
- Owner/handoff: Database | Backend | Frontend | Cross-functional
- Files/modules affected:
- Create:
- Modify:
- Dependencies:
- Acceptance criteria:

### Step 2 — <name>

Repeat in dependency order.

## 16. Files Impacted

### Create

- `<path>` — <purpose>

### Modify

- `<path>` — <purpose>

## 17. Assumptions

- <assumption and impact>

## 18. Open Questions

- <question, why it matters, and decision owner if known>

## 19. Definition of Done

- <observable completion criterion>
```

Use paths only when verified from the repository or clearly designated as proposed new paths. Keep inferred paths in the plan, not in the “existing artifacts inspected” list.
