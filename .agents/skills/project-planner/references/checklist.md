# Plan completion checklist

Before delivering the plan, verify:

- [ ] The feature goal, problem, actors, inputs/outputs, and success conditions are understood.
- [ ] Confirmed requirements are separated from assumptions, recommendations, and open questions.
- [ ] Relevant existing modules, schema, APIs, services, components, types, permissions, and conventions were inspected when source was available.
- [ ] Reuse and duplicate-feature risks were considered before proposing new files or abstractions.
- [ ] Use cases, business rules, validation, failure paths, and edge cases are concrete and testable.
- [ ] Any workflow has explicit valid and invalid state transitions.
- [ ] Data requirements identify entities, relationships, constraints, enums, indexes, and compatibility needs at the right handoff level.
- [ ] Backend impact follows existing conventions and keeps responsibilities appropriately separated.
- [ ] Frontend impact covers server state, UI state, local state, permissions, and loading/empty/error states where applicable.
- [ ] Each API contract states purpose, request, response, permission, and likely failures.
- [ ] The test strategy covers happy path, validation, permissions, not found, conflicts, business rules, and material edge cases.
- [ ] Implementation steps are dependency-ordered and each gives goal, ownership/handoff, file/module impact, dependencies, and acceptance criteria.
- [ ] The file-impact list distinguishes created and modified files and avoids presenting guesses as facts.
- [ ] The plan proposes no unneeded packages, migrations, architectural patterns, or implementation code.
- [ ] Definition of done confirms that no important dependency or unresolved decision was silently omitted.
