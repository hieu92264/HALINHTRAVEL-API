# Requirement analysis

## Requirement classification

Use these labels consistently:

| Label | Meaning | How to handle it |
| --- | --- | --- |
| Confirmed requirement | Directly stated by the requester or verifiable in an authoritative artifact. | Plan it as required work. |
| Existing-system fact | Confirmed by codebase or supplied documentation. | Cite the relevant module, path, or artifact. |
| Assumption | A necessary working interpretation that lacks confirmation. | State why it is needed and seek confirmation if it changes scope or behavior. |
| Recommendation | A proposed choice among reasonable alternatives. | Give rationale; do not treat it as a requirement. |
| Open question | A material unanswered question that prevents a confident choice. | State its impact and the decision owner when known. |

## Questions worth surfacing

Only surface questions that materially affect scope, behavior, security, data correctness, implementation effort, or acceptance. Typical categories are:

- actor/role authority and permission boundaries;
- ownership, visibility, and tenant/organization scope;
- lifecycle states, legal transitions, and reversibility;
- required fields, defaults, validation, and uniqueness;
- source of truth and interaction with existing modules;
- notifications, auditability, exports, or integrations;
- compatibility with existing records and rollout expectations.

Avoid asking for details already evident from a supplied requirement or codebase.

## Use-case format

For each material use case, state the actor, trigger, preconditions, primary flow, alternative/error flows, outcome, and affected records. This format is useful when it clarifies behavior; do not inflate a simple request into ceremonial documentation.

## Business-rule test

A business rule should be expressed as a checkable statement, such as: “Only a dispatcher assigned to the same organization may confirm a draft dispatch.” Note the enforcement layer when known (permission, request validation, service rule, database constraint), but preserve the project's conventions.

## State workflow format

When lifecycle state exists, present a compact diagram or transition table. Include:

- valid states;
- permitted transition, actor, preconditions, and outcome;
- invalid transitions and expected failure;
- whether changes are reversible and audit requirements, only when required.

Example structure:

| From | To | Allowed actor | Preconditions |
| --- | --- | --- | --- |
| draft | confirmed | authorized operator | required fields complete |

Do not assume status values, automated transitions, or audit policy without evidence.
