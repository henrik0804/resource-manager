<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    Circle,
    Clock,
    Loader2,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';

import { index as myAssignmentsIndex } from '@/actions/App/Http/Controllers/MyAssignmentController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { AccessSections } from '@/lib/access-sections';
import { dashboard } from '@/routes';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import type {
    AssigneeStatus,
    TaskAssignment,
    TaskPriority,
} from '@/types/models';

import AssignmentStatusUpdate from './my-assignments/AssignmentStatusUpdate.vue';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    myAssignments: TaskAssignment[] | null;
    statusSummary: Record<string, number> | null;
    assigneeStatuses: EnumOption[];
}

defineProps<Props>();

const page = usePage<AppPageProps>();

const isEmployee = computed(() => {
    const permissions = page.props.auth?.permissions ?? {};
    const feedback = permissions[AccessSections.EmployeeFeedback];

    return feedback?.can_read || feedback?.can_write_owned;
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

function formatDate(dateString: string | null): string {
    if (!dateString) {
        return '\u2014';
    }

    const normalized = dateString.includes('T')
        ? dateString
        : dateString.replace(' ', 'T');
    const parsed = new Date(normalized);

    if (Number.isNaN(parsed.getTime())) {
        return '\u2014';
    }

    return parsed.toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function priorityLabel(priority: TaskPriority): string {
    const map: Record<TaskPriority, string> = {
        low: 'Niedrig',
        medium: 'Mittel',
        high: 'Hoch',
        urgent: 'Dringend',
    };

    return map[priority] ?? priority;
}

function priorityVariant(
    priority: TaskPriority,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    const map: Record<
        TaskPriority,
        'default' | 'secondary' | 'destructive' | 'outline'
    > = {
        low: 'outline',
        medium: 'secondary',
        high: 'default',
        urgent: 'destructive',
    };

    return map[priority] ?? 'outline';
}

function statusLabel(status: AssigneeStatus | null): string {
    if (!status) {
        return 'Kein Status';
    }

    const map: Record<AssigneeStatus, string> = {
        pending: 'Ausstehend',
        accepted: 'Angenommen',
        in_progress: 'In Bearbeitung',
        done: 'Erledigt',
        rejected: 'Abgelehnt',
    };

    return map[status] ?? status;
}

const statusIcons: Record<string, typeof Circle> = {
    pending: Circle,
    accepted: CheckCircle2,
    in_progress: Loader2,
    done: CheckCircle2,
    rejected: XCircle,
};

const statusColors: Record<string, string> = {
    pending: 'text-muted-foreground',
    accepted: 'text-blue-600',
    in_progress: 'text-amber-600',
    done: 'text-green-600',
    rejected: 'text-red-600',
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Dashboard"
                :description="
                    isEmployee
                        ? '\u00DCbersicht Ihrer zugewiesenen Aufgaben.'
                        : 'Willkommen im Ressourcen-Manager.'
                "
            />

            <template v-if="isEmployee && myAssignments">
                <div
                    v-if="statusSummary"
                    class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5"
                >
                    <Card
                        v-for="(count, status) in statusSummary"
                        :key="status"
                    >
                        <CardContent class="flex items-center gap-3 p-4">
                            <component
                                :is="statusIcons[status] ?? Circle"
                                class="size-5 shrink-0"
                                :class="
                                    statusColors[status] ??
                                    'text-muted-foreground'
                                "
                            />
                            <div>
                                <p class="text-2xl font-semibold">
                                    {{ count }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ statusLabel(status as AssigneeStatus) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-muted-foreground">
                        Aktuelle Aufgaben
                    </h3>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="myAssignmentsIndex().url">
                            Alle anzeigen
                            <ArrowRight class="ml-1 size-3.5" />
                        </Link>
                    </Button>
                </div>

                <div
                    v-if="myAssignments.length === 0"
                    class="flex flex-1 items-center justify-center rounded-xl border border-dashed p-12"
                >
                    <p class="text-muted-foreground">
                        Ihnen sind derzeit keine Aufgaben zugewiesen.
                    </p>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
                >
                    <Card
                        v-for="assignment in myAssignments"
                        :key="assignment.id"
                    >
                        <CardHeader class="pb-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <CardTitle class="truncate text-base">
                                        {{ assignment.task?.title ?? '\u2014' }}
                                    </CardTitle>
                                    <CardDescription
                                        v-if="assignment.task?.description"
                                        class="mt-1 line-clamp-2"
                                    >
                                        {{ assignment.task.description }}
                                    </CardDescription>
                                </div>
                                <Badge
                                    v-if="assignment.task?.priority"
                                    :variant="
                                        priorityVariant(
                                            assignment.task.priority,
                                        )
                                    "
                                    class="shrink-0"
                                >
                                    {{
                                        priorityLabel(assignment.task.priority)
                                    }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div
                                class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm"
                            >
                                <div class="flex items-center gap-1.5">
                                    <CalendarDays
                                        class="size-3.5 text-muted-foreground"
                                    />
                                    <span>{{
                                        formatDate(
                                            assignment.starts_at ??
                                                assignment.task?.starts_at ??
                                                null,
                                        )
                                    }}</span>
                                    <span class="text-muted-foreground"
                                        >&ndash;</span
                                    >
                                    <span>{{
                                        formatDate(
                                            assignment.ends_at ??
                                                assignment.task?.ends_at ??
                                                null,
                                        )
                                    }}</span>
                                </div>
                                <div
                                    v-if="assignment.task?.effort_value"
                                    class="flex items-center gap-1.5"
                                >
                                    <Clock
                                        class="size-3.5 text-muted-foreground"
                                    />
                                    <span>
                                        {{ assignment.task.effort_value }}
                                        {{
                                            assignment.task.effort_unit ===
                                            'hours'
                                                ? 'Std.'
                                                : 'Tage'
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="border-t pt-4">
                                <AssignmentStatusUpdate
                                    :assignment="assignment"
                                    :assignee-statuses="assigneeStatuses"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </template>

            <template v-else>
                <div
                    class="flex flex-1 items-center justify-center rounded-xl border border-dashed p-12"
                >
                    <p class="text-muted-foreground">
                        Willkommen im Ressourcen-Manager.
                    </p>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
