# User Story Verification Findings

Full browser-based walkthrough of all 11 user stories from `spec/project.md`.  
Date: 2026-02-17

---

## Summary

| #  | User Story                 | Status  | Notes                                                             |
|----|----------------------------|---------|-------------------------------------------------------------------|
| 1  | Resource Management (CRUD) | Working | Create, edit, delete, search all functional                       |
| 2  | Task Creation              | Working | All fields (dates, effort, priority, status) work                 |
| 3  | Manual Assignment          | Working | Works after source field fix (see Fixed below)                    |
| 4  | Automated Assignment       | Working | Backend logic solid; UI confirmation + result dialog present      |
| 5  | Conflict Warning           | Working | Was broken by missing CSRF token (see Fixed below)                |
| 6  | Conflict Resolution        | Working | Alternative resources and time periods shown with "apply" buttons |
| 7  | Priority-Based Scheduling  | Working | Integrated into auto-assign; permission-gated rescheduling        |
| 8  | Visual Overview (Gantt)    | Working | Day/week/month views; absences shown in hatched red               |
| 9  | Utilization View           | Working | Summary cards, per-resource bars, timeline chart                  |
| 10 | Role Management            | Working | 5 roles, 11 permission sections, read/write/own toggles           |
| 11 | Employee Feedback          | Working | Status dropdown on dashboard + my-assignments; restricted sidebar |

---

## Bugs Fixed Directly

### 1. Missing CSRF meta tag (Critical)

**File:** `resources/views/app.blade.php`

The `<meta name="csrf-token">` tag was absent from the Blade layout. All native `fetch()` API calls (conflict checks, conflict resolution, auto-assign) silently failed with HTTP 419. Inertia requests were unaffected because Inertia uses cookies for CSRF.

**Impact:** User stories 4, 5, and 6 were completely non-functional.

**Fix:** Added `<meta name="csrf-token" content="{{ csrf_token() }}">` after the viewport meta tag.

### 2. Assignment source not defaulted to "manual" on create

**File:** `resources/js/pages/task-assignments/TaskAssignmentForm.vue`

When opening the create-assignment dialog, the `assignment_source` field was left empty. The user had to manually select "Manuell" or the form would fail validation ("The assignment source field is required"). Since the create button is for manual assignments, it should default to "manual".

**Fix:** Set `form.assignment_source = 'manual'` after `form.reset()` in the create branch.

### 3. Unicode escape in template string

**File:** `resources/js/pages/my-assignments/Index.vue`

The Heading description contained `k\u00F6nnen` which rendered as the literal escape sequence instead of "konnen".

**Fix:** Replaced with the actual UTF-8 character.

### 4. Missing `default` arm in `ScheduleController::defaultRangeEnd()`

**File:** `app/Http/Controllers/ScheduleController.php:84`

The match expression for `$precision` had explicit arms for `day`, `week`, `month` but no `default`. An unexpected precision value would throw `UnhandledMatchError`. The corresponding `defaultRangeStart()` method already had a `default` arm.

**Fix:** Changed `'month'` arm to `default`.

### 5. Dead `statusCounts` computed property

**File:** `resources/js/pages/my-assignments/Index.vue`

A `statusCounts` computed property initialized all counts to zero and returned immediately without ever populating them. It was unused in the template.

**Fix:** Removed the dead code.

---

## Remaining Issues (Larger Refactors)

### A. Capacity unit/value validation gap

**Severity:** Medium  
**Files:** `StoreResourceRequest.php`, `UpdateResourceRequest.php`, `ResourceForm.vue`

A resource can be saved with `capacity_value: 8` but `capacity_unit: null`. The validation rules mark `capacity_unit` as `nullable` without a `required_with:capacity_value` constraint. This causes downstream issues:

- `ConflictDetectionService` uses the raw numeric value without unit context
- Conflict alert messages show `null` for the unit, producing confusing output like "11.25 / 8 null"
- `UtilizationService` propagates `null` unit into utilization reports

**Recommendation:** Add `required_with` cross-validation rules on both `capacity_value` and `capacity_unit` in both store and update requests.

### B. Conflict check requires allocation_ratio to detect overloads

**Severity:** Medium  
**File:** `ConflictDetectionService.php`, `TaskAssignmentForm.vue`

When creating an assignment without specifying an `allocation_ratio`, the conflict check returns `has_conflicts: false` even if the resource is already at capacity. The service falls back to treating `null` allocation as zero, which means no overload is detected.

**Recommendation:** Either make `allocation_ratio` required when creating assignments, or have the conflict detection service assume full capacity allocation (e.g., `capacity_value`) when `allocation_ratio` is null.

### C. Duplicated helper methods across services

**Severity:** Low  
**Files:** `ConflictDetectionService.php`, `UtilizationService.php`, `AutoAssignAction.php`

The methods `resolveCapacity()`, `normalizeRatio()`, and `toCarbon()` are duplicated across three classes. Changes to capacity logic must be synchronized in all three places.

**Recommendation:** Extract these into a shared `CapacityHelper` trait or a dedicated `ResourceCapacity` value object.

### D. Auto-assign result dialog may not display when 0 tasks are assigned

**Severity:** Low  
**File:** `resources/js/pages/task-assignments/Index.vue`

When all tasks already have assignments, the auto-assign returns `{ assigned: 0, skipped: 0 }` and the result dialog opens but may appear empty or close instantly since there's nothing meaningful to show.

**Recommendation:** Show a clear message like "Alle Aufgaben sind bereits zugewiesen" (All tasks are already assigned) when both counts are zero.

### E. Permissions page toggle state visually misleading for Admin

**Severity:** Low  
**File:** `resources/js/pages/permissions/Index.vue`

When viewing Admin role permissions, all toggles appear "off" (gray) even though the header says "11 von 11 Bereichen aktiviert". The Admin role bypasses permission checks, but the visual state doesn't reflect this. Users might think Admin lacks permissions.

**Recommendation:** Either show toggles as "on" for Admin, or add a note explaining that Admin has implicit full access regardless of toggle state.
