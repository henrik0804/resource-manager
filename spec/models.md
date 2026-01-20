# Models

This document summarizes conceptual models derived from `spec/project.md`. Default ids/timestamps are omitted. Fields are intentionally minimal and can be persisted or derived as needed to avoid locking in implementation details.

## User

- name
- email
- role_id (or via `user_role` pivot)
- resource_id (optional link when a user represents a resource)

## Role

- name
- description (optional)

## Resource

- name
- resource_type_id
- capacity_value (optional)
- capacity_unit (optional)
- user_id (optional, for employee resources)

## ResourceType

- name
- description (optional)

## Qualification

- name
- description (optional)

## ResourceQualification (pivot)

- resource_id
- qualification_id
- level (optional)

## Task

- title
- description (optional)
- starts_at
- ends_at
- effort_value
- effort_unit
- priority
- status (overall)

## TaskRequirement (pivot)

- task_id
- qualification_id
- required_level (optional)

## TaskAssignment

- task_id
- resource_id
- starts_at (optional override)
- ends_at (optional override)
- allocation_ratio (optional)
- assignment_source (manual/auto)
- assignee_status (optional)

## ResourceAvailability

- resource_id
- starts_at
- ends_at
- availability_type (available/unavailable)
- recurrence_rule (optional)

## Optional or Derived Persistence

Persist only if conflict history and suggestions must be stored instead of computed on demand.

### SchedulingConflict

- task_id
- resource_id
- conflict_type
- starts_at
- ends_at

### SchedulingSuggestion

- task_id
- resource_id (optional)
- suggested_starts_at
- suggested_ends_at
- reason
- score (optional)

## Tradeoffs

- Roles: single `role_id` is simple but limits users to one role; a `user_role` pivot supports multi-role access at the cost of more joins.
- Resource linkage: mapping employees via `resource.user_id` unifies planning but couples resources to accounts; keeping them separate avoids that coupling.
- Status tracking: `task.status` is simpler; `task_assignment.assignee_status` supports multi-resource tasks and per-person updates.
- Availability modeling: explicit time blocks are easy to query; recurrence rules or external calendars are more flexible but add complexity.
- Qualifications: normalized `qualification` tables improve matching/validation; free-form tags or JSON are faster to change but harder to query.
