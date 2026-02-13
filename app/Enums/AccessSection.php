<?php

declare(strict_types=1);

namespace App\Enums;

enum AccessSection: string
{
    case ResourceManagement = 'resource_management';
    case TaskCreation = 'task_creation';
    case ManualAssignment = 'manual_assignment';
    case AutomatedAssignment = 'automated_assignment';
    case ConflictWarning = 'conflict_warning';
    case ConflictResolution = 'conflict_resolution';
    case PriorityScheduling = 'priority_scheduling';
    case VisualOverview = 'visual_overview';
    case UtilizationView = 'utilization_view';
    case RoleManagement = 'role_management';
    case EmployeeFeedback = 'employee_feedback';

    public function label(): string
    {
        return match ($this) {
            self::ResourceManagement => 'Ressourcenverwaltung',
            self::TaskCreation => 'Aufgabenerstellung',
            self::ManualAssignment => 'Manuelle Zuweisung',
            self::AutomatedAssignment => 'Automatisierte Zuweisung',
            self::ConflictWarning => 'Konfliktwarnungen',
            self::ConflictResolution => 'Konfliktlösung',
            self::PriorityScheduling => 'Prioritätsbasierte Planung',
            self::VisualOverview => 'Zeitliche Gesamtansicht',
            self::UtilizationView => 'Auslastungsansicht',
            self::RoleManagement => 'Rollenverwaltung',
            self::EmployeeFeedback => 'Mitarbeiterfeedback',
        };
    }
}
