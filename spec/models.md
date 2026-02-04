# Models

This document summarizes conceptual models derived from `spec/project.md`. Default ids/timestamps are omitted. Fields are intentionally minimal and can be persisted or derived as needed to avoid locking in implementation details.

## User

- name
- email
- role_id

## Role

- name
- description (optional)

## Resource

- name
- resource_type_id
- capacity_value (optional)
- capacity_unit (optional)
- user_id (optional, identity link to User when a resource is also a platform user)

## ResourceType

- name
- description (optional)

## Qualification

- name
- description (optional)
- resource_type_id (optional)

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

## ResourceAbsence

- resource_id
- starts_at
- ends_at
- recurrence_rule (optional)
