import type { AccessSection } from '@/lib/access-sections';

export interface ResourceType {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
    resources_count?: number;
    qualifications_count?: number;
    [key: string]: unknown;
}

export interface Role {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
    users_count?: number;
    [key: string]: unknown;
}

export interface Permission {
    id: number;
    role_id: number;
    section: AccessSection;
    can_read: boolean;
    can_write: boolean;
    can_write_owned: boolean;
    created_at: string;
    updated_at: string;
    role?: Role;
    [key: string]: unknown;
}

export interface Qualification {
    id: number;
    name: string;
    description: string | null;
    resource_type_id: number | null;
    created_at: string;
    updated_at: string;
    resource_type?: ResourceType;
    [key: string]: unknown;
}

export interface Resource {
    id: number;
    name: string;
    resource_type_id: number;
    capacity_value: string | null;
    capacity_unit: CapacityUnit | null;
    user_id: number | null;
    created_at: string;
    updated_at: string;
    resource_type?: ResourceType;
    user?: import('@/types').User;
    [key: string]: unknown;
}

export interface ResourceAbsence {
    id: number;
    resource_id: number;
    starts_at: string;
    ends_at: string;
    recurrence_rule: string | null;
    created_at: string;
    updated_at: string;
    resource?: Resource;
    [key: string]: unknown;
}

export interface ResourceQualification {
    id: number;
    resource_id: number;
    qualification_id: number;
    level: QualificationLevel | null;
    created_at: string;
    updated_at: string;
    resource?: Resource;
    qualification?: Qualification;
    [key: string]: unknown;
}

export type QualificationLevel =
    | 'beginner'
    | 'intermediate'
    | 'advanced'
    | 'expert';

export type CapacityUnit = 'hours_per_day' | 'slots';

export type EffortUnit = 'hours' | 'days';

export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';

export type TaskStatus = 'planned' | 'in_progress' | 'blocked' | 'done';

export type AssignmentSource = 'manual' | 'automated';

export type AssigneeStatus =
    | 'pending'
    | 'accepted'
    | 'in_progress'
    | 'done'
    | 'rejected';

export interface Task {
    id: number;
    title: string;
    description: string | null;
    starts_at: string;
    ends_at: string;
    effort_value: string;
    effort_unit: EffortUnit;
    priority: TaskPriority;
    status: TaskStatus;
    created_at: string;
    updated_at: string;
    requirements_count?: number;
    assignments_count?: number;
    [key: string]: unknown;
}

export interface TaskRequirement {
    id: number;
    task_id: number;
    qualification_id: number;
    required_level: QualificationLevel | null;
    created_at: string;
    updated_at: string;
    task?: Task;
    qualification?: Qualification;
    [key: string]: unknown;
}

export interface TaskAssignment {
    id: number;
    task_id: number;
    resource_id: number;
    starts_at: string | null;
    ends_at: string | null;
    allocation_ratio: string | null;
    assignment_source: AssignmentSource;
    assignee_status: AssigneeStatus | null;
    created_at: string;
    updated_at: string;
    task?: Task;
    resource?: Resource;
    [key: string]: unknown;
}

export interface AutoAssignBlockingAssignment {
    id: number;
    task_id: number;
    task_title: string;
    task_priority: TaskPriority;
    starts_at: string | null;
    ends_at: string | null;
    assignment_source: AssignmentSource;
}

export interface AutoAssignSuggestionResource {
    resource: {
        id: number;
        name: string;
        utilization_percentage: number | null;
    };
    conflict_types: string[];
    blocking_assignments: AutoAssignBlockingAssignment[];
}

export interface AutoAssignSuggestion {
    task: {
        id: number;
        title: string;
        priority: TaskPriority;
        starts_at: string | null;
        ends_at: string | null;
    };
    resources: AutoAssignSuggestionResource[];
}

export interface AutoAssignResponse {
    assigned: number;
    skipped: number;
    suggestions: AutoAssignSuggestion[];
}

export interface ConflictResolutionResource {
    id: number;
    name: string;
    capacity_value: string | null;
    capacity_unit: CapacityUnit | null;
}

export interface ConflictResolutionResponse {
    alternatives: ConflictResolutionResource[];
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    first_page_url: string;
    last_page_url: string;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
    links: PaginationLink[];
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
