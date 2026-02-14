<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import CheckConflicts from '@/actions/App/Http/Controllers/CheckConflictsController';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/TaskAssignmentController';
import type { ConflictCheckResponse } from '@/components/ConflictAlert.vue';
import ConflictAlert from '@/components/ConflictAlert.vue';
import FormDialog from '@/components/FormDialog.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Resource, Task, TaskAssignment } from '@/types/models';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    open: boolean;
    taskAssignment?: TaskAssignment | null;
    tasks: Pick<Task, 'id' | 'title'>[];
    resources: Pick<Resource, 'id' | 'name'>[];
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

function canCheckConflicts(): boolean {
    if (!form.resource_id) {
        return false;
    }

    const hasAssignmentDates = form.starts_at !== '' && form.ends_at !== '';
    const hasTaskId = form.task_id !== null;

    return hasAssignmentDates || hasTaskId;
}

async function checkConflicts(): Promise<void> {
    if (!canCheckConflicts()) {
        conflictResult.value = null;

        return;
    }

    conflictAbortController?.abort();
    conflictAbortController = new AbortController();

    isCheckingConflicts.value = true;

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

        const response = await fetch(CheckConflicts.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
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

            if (conflictCheckTimeout) {
                clearTimeout(conflictCheckTimeout);
            }
        }
    },
);

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
            <Label for="assignment-allocation">Auslastung</Label>
            <Input
                id="assignment-allocation"
                v-model="form.allocation_ratio"
                type="number"
                min="0"
                max="1"
                step="0.01"
                placeholder="z.B. 0.5 für 50%"
                :disabled="form.processing"
            />
            <InputError :message="form.errors.allocation_ratio" />
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
    </FormDialog>
</template>
