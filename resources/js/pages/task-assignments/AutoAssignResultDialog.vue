<script setup lang="ts">
import {
    ArrowRightLeft,
    CheckCircle,
    SkipForward,
    TriangleAlert,
    User,
} from 'lucide-vue-next';
import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type {
    AutoAssignResponse,
    AutoAssignSuggestion,
    TaskPriority,
} from '@/types/models';

interface Props {
    open: boolean;
    result: AutoAssignResponse | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const rescheduledCount = computed(() => {
    if (!props.result) {
        return 0;
    }

    return props.result.rescheduled?.length ?? 0;
});

const priorityLabels: Record<TaskPriority, string> = {
    urgent: 'Dringend',
    high: 'Hoch',
    medium: 'Mittel',
    low: 'Niedrig',
};

const conflictTypeLabels: Record<string, string> = {
    double_booked: 'Doppelbuchung',
    overloaded: 'Überlastet',
    absent: 'Abwesend',
};

function priorityBadgeVariant(
    priority: TaskPriority,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (priority === 'urgent') {
        return 'destructive';
    }

    if (priority === 'high') {
        return 'default';
    }

    return 'secondary';
}

function formatDate(dateString: string | null): string {
    if (!dateString) {
        return '--';
    }

    return new Date(dateString).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function formatUtilization(percentage: number | null): string {
    if (percentage === null) {
        return '--';
    }

    return `${Math.round(percentage)} %`;
}

function formatPeriod(start: string | null, end: string | null): string {
    if (!start || !end) {
        return '--';
    }

    return `${formatDate(start)} – ${formatDate(end)}`;
}

function hasSuggestions(result: AutoAssignResponse): boolean {
    return result.suggestions.length > 0;
}

function suggestionSummary(suggestion: AutoAssignSuggestion): string {
    const count = suggestion.resources.length;
    const noun = count === 1 ? 'Ressource' : 'Ressourcen';

    return `${count} ${noun} mit Konflikten`;
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Ergebnis der Auto-Zuweisung</DialogTitle>
                <DialogDescription>
                    Zusammenfassung des automatischen Zuweisungslaufs.
                </DialogDescription>
            </DialogHeader>

            <div v-if="result" class="space-y-4">
                <div class="flex flex-wrap gap-4">
                    <div
                        class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"
                    >
                        <CheckCircle
                            class="size-4 text-emerald-600 dark:text-emerald-400"
                        />
                        <span class="font-medium">
                            {{ result.assigned }}
                        </span>
                        zugewiesen
                    </div>
                    <div
                        class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"
                    >
                        <SkipForward class="size-4 text-muted-foreground" />
                        <span class="font-medium">
                            {{ result.skipped }}
                        </span>
                        übersprungen
                    </div>
                    <div
                        v-if="rescheduledCount > 0"
                        class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"
                    >
                        <ArrowRightLeft class="size-4 text-amber-600" />
                        <span class="font-medium">{{ rescheduledCount }}</span>
                        verschoben
                    </div>
                </div>

                <template v-if="rescheduledCount > 0">
                    <div class="space-y-1">
                        <h3 class="text-sm font-medium">
                            Verschobene Aufgaben
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Niedriger priorisierte Aufgaben wurden in
                            alternative Zeitfenster verschoben.
                        </p>
                    </div>

                    <div class="space-y-2 rounded-md border p-3">
                        <div
                            v-for="entry in result.rescheduled"
                            :key="entry.assignment_id"
                            class="rounded-md border bg-muted/40 p-2 text-sm"
                        >
                            <div class="flex items-center gap-2">
                                <span class="font-medium">
                                    {{ entry.task_title }}
                                </span>
                                <Badge
                                    :variant="
                                        priorityBadgeVariant(
                                            entry.task_priority,
                                        )
                                    "
                                >
                                    {{
                                        priorityLabels[entry.task_priority] ??
                                        entry.task_priority
                                    }}
                                </Badge>
                            </div>
                            <div class="mt-1 text-xs text-muted-foreground">
                                <div>
                                    Alt:
                                    {{
                                        formatPeriod(
                                            entry.previous_starts_at,
                                            entry.previous_ends_at,
                                        )
                                    }}
                                </div>
                                <div>
                                    Neu:
                                    {{
                                        formatPeriod(
                                            entry.starts_at,
                                            entry.ends_at,
                                        )
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-if="hasSuggestions(result)">
                    <div class="space-y-1">
                        <h3 class="text-sm font-medium">
                            Vorschläge zur Umplanung
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Diese Aufgaben konnten nicht zugewiesen werden, aber
                            es gibt Ressourcen, deren blockierende Zuweisungen
                            eine niedrigere Priorität haben.
                        </p>
                    </div>

                    <div
                        class="max-h-80 space-y-3 overflow-y-auto rounded-md border p-3"
                    >
                        <div
                            v-for="suggestion in result.suggestions"
                            :key="suggestion.task.id"
                            class="space-y-2"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium">
                                    {{ suggestion.task.title }}
                                </span>
                                <Badge
                                    :variant="
                                        priorityBadgeVariant(
                                            suggestion.task.priority,
                                        )
                                    "
                                >
                                    {{
                                        priorityLabels[suggestion.task.priority]
                                    }}
                                </Badge>
                                <span class="text-xs text-muted-foreground">
                                    {{ formatDate(suggestion.task.starts_at) }}
                                    –
                                    {{ formatDate(suggestion.task.ends_at) }}
                                </span>
                            </div>

                            <p class="text-xs text-muted-foreground">
                                {{ suggestionSummary(suggestion) }}
                            </p>

                            <div class="space-y-2 pl-4">
                                <div
                                    v-for="candidate in suggestion.resources"
                                    :key="candidate.resource.id"
                                    class="rounded-md border bg-muted/40 p-2 text-sm"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <User
                                                class="size-3.5 text-muted-foreground"
                                            />
                                            <span class="font-medium">
                                                {{ candidate.resource.name }}
                                            </span>
                                        </div>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            Auslastung:
                                            {{
                                                formatUtilization(
                                                    candidate.resource
                                                        .utilization_percentage,
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <Badge
                                            v-for="conflictType in candidate.conflict_types"
                                            :key="conflictType"
                                            variant="outline"
                                            class="text-amber-600 dark:text-amber-400"
                                        >
                                            <TriangleAlert class="size-3" />
                                            {{
                                                conflictTypeLabels[
                                                    conflictType
                                                ] ?? conflictType
                                            }}
                                        </Badge>
                                    </div>

                                    <div
                                        v-if="
                                            candidate.blocking_assignments
                                                .length > 0
                                        "
                                        class="mt-1.5 space-y-1"
                                    >
                                        <p
                                            class="text-xs font-medium text-muted-foreground"
                                        >
                                            Blockierende Zuweisungen:
                                        </p>
                                        <div
                                            v-for="blocking in candidate.blocking_assignments"
                                            :key="blocking.id"
                                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                        >
                                            <ArrowRightLeft class="size-3" />
                                            <span>
                                                {{ blocking.task_title }}
                                            </span>
                                            <Badge
                                                :variant="
                                                    priorityBadgeVariant(
                                                        blocking.task_priority,
                                                    )
                                                "
                                            >
                                                {{
                                                    priorityLabels[
                                                        blocking.task_priority
                                                    ]
                                                }}
                                            </Badge>
                                            <span>
                                                {{
                                                    formatDate(
                                                        blocking.starts_at,
                                                    )
                                                }}
                                                –
                                                {{
                                                    formatDate(blocking.ends_at)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <DialogFooter>
                <DialogClose as-child>
                    <Button variant="outline">Schließen</Button>
                </DialogClose>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
