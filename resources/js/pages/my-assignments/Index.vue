<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarDays, Clock } from 'lucide-vue-next';

import { index } from '@/actions/App/Http/Controllers/MyAssignmentController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type {
    Paginated,
    TaskAssignment,
    TaskPriority,
    TaskStatus,
} from '@/types/models';

import AssignmentStatusUpdate from './AssignmentStatusUpdate.vue';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    assignments: Paginated<TaskAssignment>;
    assigneeStatuses: EnumOption[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Meine Aufgaben', href: index().url },
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

function priorityLabel(priority: TaskPriority): string {
    const map: Record<TaskPriority, string> = {
        low: 'Niedrig',
        medium: 'Mittel',
        high: 'Hoch',
        urgent: 'Dringend',
    };

    return map[priority] ?? priority;
}

function taskStatusLabel(status: TaskStatus): string {
    const map: Record<TaskStatus, string> = {
        planned: 'Geplant',
        in_progress: 'In Bearbeitung',
        blocked: 'Blockiert',
        done: 'Erledigt',
    };

    return map[status] ?? status;
}

function taskStatusVariant(
    status: TaskStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    const map: Record<
        TaskStatus,
        'default' | 'secondary' | 'destructive' | 'outline'
    > = {
        planned: 'outline',
        in_progress: 'default',
        blocked: 'destructive',
        done: 'secondary',
    };

    return map[status] ?? 'outline';
}
</script>

<template>
    <Head title="Meine Aufgaben" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Meine Aufgaben"
                description="Hier sehen Sie Ihre zugewiesenen Aufgaben und können den Bearbeitungsstatus aktualisieren."
            />

            <div
                v-if="assignments.data.length === 0"
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
                    v-for="assignment in assignments.data"
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
                                    priorityVariant(assignment.task.priority)
                                "
                                class="shrink-0"
                            >
                                {{ priorityLabel(assignment.task.priority) }}
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="flex items-center gap-1.5">
                                <CalendarDays
                                    class="size-3.5 shrink-0 text-muted-foreground"
                                />
                                <span class="text-muted-foreground"
                                    >Beginn:</span
                                >
                                <span>{{
                                    formatDate(
                                        assignment.starts_at ??
                                            assignment.task?.starts_at ??
                                            null,
                                    )
                                }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <CalendarDays
                                    class="size-3.5 shrink-0 text-muted-foreground"
                                />
                                <span class="text-muted-foreground">Ende:</span>
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
                                    class="size-3.5 shrink-0 text-muted-foreground"
                                />
                                <span class="text-muted-foreground"
                                    >Aufwand:</span
                                >
                                <span>
                                    {{ assignment.task.effort_value }}
                                    {{
                                        assignment.task.effort_unit === 'hours'
                                            ? 'Std.'
                                            : 'Tage'
                                    }}
                                </span>
                            </div>
                            <div
                                v-if="assignment.task?.status"
                                class="flex items-center gap-1.5"
                            >
                                <Badge
                                    :variant="
                                        taskStatusVariant(
                                            assignment.task.status,
                                        )
                                    "
                                    class="text-xs"
                                >
                                    {{
                                        taskStatusLabel(assignment.task.status)
                                    }}
                                </Badge>
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
        </div>
    </AppLayout>
</template>
