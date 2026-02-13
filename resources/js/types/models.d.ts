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
    capacity_unit: string | null;
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

export interface Task {
    id: number;
    title: string;
    description: string | null;
    starts_at: string;
    ends_at: string;
    effort_value: string;
    effort_unit: string;
    priority: string;
    status: string;
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
    assignment_source: string;
    assignee_status: string | null;
    created_at: string;
    updated_at: string;
    task?: Task;
    resource?: Resource;
    [key: string]: unknown;
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
