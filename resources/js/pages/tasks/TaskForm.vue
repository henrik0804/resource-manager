<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import { store, update } from '@/actions/App/Http/Controllers/TaskController';
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
import { Textarea } from '@/components/ui/textarea';
import type { Task } from '@/types/models';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    open: boolean;
    task?: Task | null;
    priorities: EnumOption[];
    statuses: EnumOption[];
    effortUnits: EnumOption[];
}

const props = withDefaults(defineProps<Props>(), {
    task: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.task !== null;

function formatDateForInput(dateString: string): string {
    return dateString.substring(0, 10);
}

const form = useForm({
    title: '',
    description: '',
    starts_at: '',
    ends_at: '',
    effort_value: '' as string | number,
    effort_unit: '',
    priority: '',
    status: '',
});

watch(
    () => props.open,
    (open) => {
        if (open && props.task) {
            form.title = props.task.title;
            form.description = props.task.description ?? '';
            form.starts_at = formatDateForInput(props.task.starts_at);
            form.ends_at = formatDateForInput(props.task.ends_at);
            form.effort_value = props.task.effort_value;
            form.effort_unit = props.task.effort_unit;
            form.priority = props.task.priority;
            form.status = props.task.status;
        } else if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    const action = isEditing() ? update(props.task!.id) : store();
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
        :title="isEditing() ? 'Aufgabe bearbeiten' : 'Aufgabe erstellen'"
        :description="
            isEditing()
                ? 'Ändern Sie die Eigenschaften dieser Aufgabe.'
                : 'Erstellen Sie eine neue Aufgabe.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="task-title"
                >Titel <span class="text-destructive">*</span></Label
            >
            <Input
                id="task-title"
                v-model="form.title"
                placeholder="Titel der Aufgabe"
                :disabled="form.processing"
                required
            />
            <InputError :message="form.errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="task-description">Beschreibung</Label>
            <Textarea
                id="task-description"
                v-model="form.description"
                placeholder="Optionale Beschreibung"
                :disabled="form.processing"
            />
            <InputError :message="form.errors.description" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="task-starts-at"
                    >Beginn <span class="text-destructive">*</span></Label
                >
                <Input
                    id="task-starts-at"
                    v-model="form.starts_at"
                    type="date"
                    :disabled="form.processing"
                    required
                />
                <InputError :message="form.errors.starts_at" />
            </div>

            <div class="grid gap-2">
                <Label for="task-ends-at"
                    >Ende <span class="text-destructive">*</span></Label
                >
                <Input
                    id="task-ends-at"
                    v-model="form.ends_at"
                    type="date"
                    :disabled="form.processing"
                    required
                />
                <InputError :message="form.errors.ends_at" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="task-effort-value"
                    >Aufwand <span class="text-destructive">*</span></Label
                >
                <Input
                    id="task-effort-value"
                    v-model="form.effort_value"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="0"
                    :disabled="form.processing"
                    required
                />
                <InputError :message="form.errors.effort_value" />
            </div>

            <div class="grid gap-2">
                <Label for="task-effort-unit"
                    >Einheit <span class="text-destructive">*</span></Label
                >
                <Select
                    :model-value="form.effort_unit"
                    :disabled="form.processing"
                    @update:model-value="form.effort_unit = $event"
                >
                    <SelectTrigger id="task-effort-unit">
                        <SelectValue placeholder="Einheit wählen" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="u in effortUnits"
                            :key="u.value"
                            :value="u.value"
                        >
                            {{ u.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.effort_unit" />
            </div>
        </div>
        <p class="-mt-1 text-xs text-muted-foreground">
            Geschätzter Gesamtaufwand für diese Aufgabe.
            <span v-if="form.effort_unit === 'days'">
                1 Tag = 8 Arbeitsstunden.
            </span>
        </p>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="task-priority"
                    >Priorität <span class="text-destructive">*</span></Label
                >
                <Select
                    :model-value="form.priority"
                    :disabled="form.processing"
                    @update:model-value="form.priority = $event"
                >
                    <SelectTrigger id="task-priority">
                        <SelectValue placeholder="Priorität wählen" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="p in priorities"
                            :key="p.value"
                            :value="p.value"
                        >
                            {{ p.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.priority" />
            </div>

            <div class="grid gap-2">
                <Label for="task-status"
                    >Status <span class="text-destructive">*</span></Label
                >
                <Select
                    :model-value="form.status"
                    :disabled="form.processing"
                    @update:model-value="form.status = $event"
                >
                    <SelectTrigger id="task-status">
                        <SelectValue placeholder="Status wählen" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="s in statuses"
                            :key="s.value"
                            :value="s.value"
                        >
                            {{ s.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.status" />
            </div>
        </div>
    </FormDialog>
</template>
