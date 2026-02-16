<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import CheckConflicts from '@/actions/App/Http/Controllers/CheckConflictsController';
import ConflictResolution from '@/actions/App/Http/Controllers/ConflictResolutionController';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/TaskAssignmentController';
import type { ConflictCheckResponse } from '@/components/ConflictAlert.vue';
import ConflictAlert from '@/components/ConflictAlert.vue';
import FormDialog from '@/components/FormDialog.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    ConflictResolutionResource,
    ConflictResolutionResponse,
    Resource,
    Task,
    TaskAssignment,
} from '@/types/models';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    open: boolean;
    taskAssignment?: TaskAssignment | null;
    tasks: Pick<Task, 'id' | 'title'>[];
    resources: Pick<
        Resource,
        'id' | 'name' | 'capacity_unit' | 'capacity_value'
    >[];
    assignmentSources: EnumOption[];
    assigneeStatuses: EnumOption[];
}

const props = withDefaults(defineProps<Props>(), {
    taskAssignment: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.taskAssignment !== null;

function formatDateForInput(dateString: string | null): string {
    if (!dateString) {
        return '';
    }
    return dateString.substring(0, 10);
}

const form = useForm({
    task_id: null as number | null,
    resource_id: null as number | null,
    starts_at: '',
    ends_at: '',
    allocation_ratio: '' as string | number,
    assignment_source: '',
    assignee_status: '',
});

const conflictResult = ref<ConflictCheckResponse | null>(null);
const isCheckingConflicts = ref(false);
let conflictCheckTimeout: ReturnType<typeof setTimeout> | null = null;
let conflictAbortController: AbortController | null = null;
const alternativeResources = ref<ConflictResolutionResource[]>([]);
const isLoadingAlternatives = ref(false);
let alternativesAbortController: AbortController | null = null;

const selectedResource = computed(() =>
    props.resources.find((resource) => resource.id === form.resource_id),
);

const allocationUnit = computed(
    () => selectedResource.value?.capacity_unit ?? null,
);

const allocationMax = computed(() => {
    const value = selectedResource.value?.capacity_value;

    if (value === null || value === undefined || value === '') {
        return null;
    }

    const numeric = Number(value);

    return Number.isFinite(numeric) && numeric > 0 ? numeric : null;
});

const allocationStep = computed(() =>
    allocationUnit.value === 'slots' ? 1 : 0.01,
);

const allocationLabel = computed(() => {
    if (allocationUnit.value === 'hours_per_day') {
        return 'Auslastung (Std./Tag)';
    }

    if (allocationUnit.value === 'slots') {
        return 'Auslastung (Slots)';
    }

    return 'Auslastung';
});

function formatQuantity(value: number): string {
    return value % 1 === 0 ? value.toString() : value.toFixed(2);
}

function formatCapacity(resource: ConflictResolutionResource): string | null {
    if (resource.capacity_value === null || resource.capacity_unit === null) {
        return null;
    }

    const numeric = Number(resource.capacity_value);
    const value = Number.isFinite(numeric)
        ? formatQuantity(numeric)
        : resource.capacity_value;

    if (resource.capacity_unit === 'hours_per_day') {
        return `${value} Std./Tag`;
    }

    if (resource.capacity_unit === 'slots') {
        return `${value} Slots`;
    }

    return value.toString();
}

const allocationPlaceholder = computed(() => {
    if (allocationUnit.value === 'hours_per_day') {
        return 'z.B. 4';
    }

    if (allocationUnit.value === 'slots') {
        return 'z.B. 1';
    }

    return 'z.B. 1';
});

const allocationHint = computed(() => {
    if (allocationUnit.value === 'hours_per_day') {
        const max = allocationMax.value;

        return max !== null
            ? `Angabe in Stunden pro Tag (max. ${formatQuantity(max)} Std./Tag).`
            : 'Angabe in Stunden pro Tag.';
    }

    if (allocationUnit.value === 'slots') {
        const max = allocationMax.value;
        const maxHint =
            max !== null ? ` Max. ${formatQuantity(max)} Slots.` : '';

        return `Angabe in parallelen Slots.${maxHint} Beispiel: 3 Drucker = 3 Slots.`;
    }

    return 'Ressource auswählen, um die Auslastungseinheit zu sehen.';
});

function canCheckConflicts(): boolean {
    if (!form.resource_id) {
        return false;
    }

    const hasAssignmentDates = form.starts_at !== '' && form.ends_at !== '';
    const hasTaskId = form.task_id !== null;

    return hasAssignmentDates || hasTaskId;
}

function resolveCsrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function loadAlternatives(): Promise<void> {
    if (!canCheckConflicts()) {
        alternativeResources.value = [];
        alternativesAbortController?.abort();

        return;
    }

    alternativesAbortController?.abort();
    alternativesAbortController = new AbortController();

    isLoadingAlternatives.value = true;
    alternativeResources.value = [];

    try {
        const response = await fetch(ConflictResolution.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': resolveCsrfToken(),
            },
            signal: alternativesAbortController.signal,
            body: JSON.stringify({
                current_resource_id: form.resource_id,
                task_id: form.task_id,
                starts_at: form.starts_at || null,
                ends_at: form.ends_at || null,
                allocation_ratio: form.allocation_ratio || null,
                exclude_assignment_id: props.taskAssignment?.id ?? null,
            }),
        });

        if (response.ok) {
            const payload =
                (await response.json()) as ConflictResolutionResponse;
            alternativeResources.value = payload.alternatives ?? [];
        }
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            return;
        }
    } finally {
        isLoadingAlternatives.value = false;
    }
}

async function checkConflicts(): Promise<void> {
    if (!canCheckConflicts()) {
        conflictResult.value = null;
        alternativeResources.value = [];
        alternativesAbortController?.abort();

        return;
    }

    conflictAbortController?.abort();
    conflictAbortController = new AbortController();

    isCheckingConflicts.value = true;

    try {
        const response = await fetch(CheckConflicts.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': resolveCsrfToken(),
            },
            signal: conflictAbortController.signal,
            body: JSON.stringify({
                resource_id: form.resource_id,
                task_id: form.task_id,
                starts_at: form.starts_at || null,
                ends_at: form.ends_at || null,
                allocation_ratio: form.allocation_ratio || null,
                exclude_assignment_id: props.taskAssignment?.id ?? null,
            }),
        });

        if (response.ok) {
            conflictResult.value =
                (await response.json()) as ConflictCheckResponse;

            if (conflictResult.value.has_conflicts) {
                await loadAlternatives();
            } else {
                alternativeResources.value = [];
            }
        } else {
            conflictResult.value = null;
            alternativeResources.value = [];
        }
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            return;
        }
    } finally {
        isCheckingConflicts.value = false;
    }
}

function scheduleConflictCheck(): void {
    if (conflictCheckTimeout) {
        clearTimeout(conflictCheckTimeout);
    }
    conflictCheckTimeout = setTimeout(checkConflicts, 500);
}

watch(
    () => [
        form.resource_id,
        form.task_id,
        form.starts_at,
        form.ends_at,
        form.allocation_ratio,
    ],
    () => {
        scheduleConflictCheck();
    },
);

watch(
    () => props.open,
    (open) => {
        if (open && props.taskAssignment) {
            form.task_id = props.taskAssignment.task_id;
            form.resource_id = props.taskAssignment.resource_id;
            form.starts_at = formatDateForInput(props.taskAssignment.starts_at);
            form.ends_at = formatDateForInput(props.taskAssignment.ends_at);
            form.allocation_ratio = props.taskAssignment.allocation_ratio ?? '';
            form.assignment_source = props.taskAssignment.assignment_source;
            form.assignee_status = props.taskAssignment.assignee_status ?? '';
        } else if (open) {
            form.reset();
            form.clearErrors();
        }

        if (!open) {
            conflictResult.value = null;
            isCheckingConflicts.value = false;
            conflictAbortController?.abort();
            alternativeResources.value = [];
            isLoadingAlternatives.value = false;
            alternativesAbortController?.abort();

            if (conflictCheckTimeout) {
                clearTimeout(conflictCheckTimeout);
            }
        }
    },
);

function applyAlternative(resourceId: number): void {
    form.resource_id = resourceId;
    scheduleConflictCheck();
}

function submit() {
    const action = isEditing() ? update(props.taskAssignment!.id) : store();
    const method = isEditing() ? 'put' : 'post';

    form[method](action.url, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
            form.reset();
        },
    });
}
</script>

<template>
    <FormDialog
        :open="open"
        :title="isEditing() ? 'Zuweisung bearbeiten' : 'Zuweisung erstellen'"
        :description="
            isEditing()
                ? 'Ändern Sie die Aufgabenzuweisung.'
                : 'Erstellen Sie eine neue Aufgabenzuweisung.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="assignment-task"
                >Aufgabe <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.task_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.task_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="assignment-task">
                    <SelectValue placeholder="Aufgabe wählen" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="task in tasks"
                        :key="task.id"
                        :value="task.id.toString()"
                    >
                        {{ task.title }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.task_id" />
        </div>

        <div class="grid gap-2">
            <Label for="assignment-resource"
                >Ressource <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.resource_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.resource_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="assignment-resource">
                    <SelectValue placeholder="Ressource wählen" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="resource in resources"
                        :key="resource.id"
                        :value="resource.id.toString()"
                    >
                        {{ resource.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.resource_id" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="assignment-starts-at">Beginn</Label>
                <Input
                    id="assignment-starts-at"
                    v-model="form.starts_at"
                    type="date"
                    :disabled="form.processing"
                />
                <InputError :message="form.errors.starts_at" />
            </div>

            <div class="grid gap-2">
                <Label for="assignment-ends-at">Ende</Label>
                <Input
                    id="assignment-ends-at"
                    v-model="form.ends_at"
                    type="date"
                    :disabled="form.processing"
                />
                <InputError :message="form.errors.ends_at" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="assignment-allocation">{{ allocationLabel }}</Label>
            <Input
                id="assignment-allocation"
                v-model="form.allocation_ratio"
                type="number"
                min="0"
                :max="allocationMax ?? undefined"
                :step="allocationStep"
                :placeholder="allocationPlaceholder"
                :disabled="form.processing"
            />
            <InputError :message="form.errors.allocation_ratio" />
            <p class="-mt-1 text-xs text-muted-foreground">
                {{ allocationHint }}
            </p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="assignment-source"
                    >Quelle <span class="text-destructive">*</span></Label
                >
                <Select
                    :model-value="form.assignment_source"
                    :disabled="form.processing"
                    @update:model-value="form.assignment_source = $event"
                >
                    <SelectTrigger id="assignment-source">
                        <SelectValue placeholder="Quelle wählen" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="s in assignmentSources"
                            :key="s.value"
                            :value="s.value"
                        >
                            {{ s.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.assignment_source" />
            </div>

            <div class="grid gap-2">
                <Label for="assignment-status">Status</Label>
                <Select
                    :model-value="form.assignee_status ?? ''"
                    :disabled="form.processing"
                    @update:model-value="form.assignee_status = $event || ''"
                >
                    <SelectTrigger id="assignment-status">
                        <SelectValue placeholder="Kein Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="s in assigneeStatuses"
                            :key="s.value"
                            :value="s.value"
                        >
                            {{ s.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.assignee_status" />
            </div>
        </div>

        <ConflictAlert
            v-if="conflictResult?.has_conflicts"
            :conflicts="conflictResult.conflicts"
        />

        <div v-if="conflictResult?.has_conflicts" class="space-y-2">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <p class="text-sm font-medium">Alternative Ressourcen</p>
                    <p class="text-xs text-muted-foreground">
                        Sortiert nach geringster Auslastung.
                    </p>
                </div>
                <span
                    v-if="isLoadingAlternatives"
                    class="text-xs text-muted-foreground"
                >
                    Suche…
                </span>
            </div>

            <p
                v-if="
                    !isLoadingAlternatives && alternativeResources.length === 0
                "
                class="text-xs text-muted-foreground"
            >
                Keine passenden Alternativen gefunden.
            </p>

            <div v-else class="grid gap-2">
                <div
                    v-for="alternative in alternativeResources"
                    :key="alternative.id"
                    class="flex items-center justify-between gap-3 rounded-md border bg-muted/30 px-3 py-2"
                >
                    <div class="grid">
                        <span class="text-sm font-medium">
                            {{ alternative.name }}
                        </span>
                        <span
                            v-if="formatCapacity(alternative)"
                            class="text-xs text-muted-foreground"
                        >
                            Kapazität: {{ formatCapacity(alternative) }}
                        </span>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="form.processing"
                        @click="applyAlternative(alternative.id)"
                    >
                        Übernehmen
                    </Button>
                </div>
            </div>
        </div>
    </FormDialog>
</template>
