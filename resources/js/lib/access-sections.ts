export const AccessSections = {
    ResourceManagement: 'resource_management',
    TaskCreation: 'task_creation',
    ManualAssignment: 'manual_assignment',
    AutomatedAssignment: 'automated_assignment',
    ConflictWarning: 'conflict_warning',
    ConflictResolution: 'conflict_resolution',
    PriorityScheduling: 'priority_scheduling',
    VisualOverview: 'visual_overview',
    UtilizationView: 'utilization_view',
    RoleManagement: 'role_management',
    EmployeeFeedback: 'employee_feedback',
} as const;

export type AccessSection =
    (typeof AccessSections)[keyof typeof AccessSections];
