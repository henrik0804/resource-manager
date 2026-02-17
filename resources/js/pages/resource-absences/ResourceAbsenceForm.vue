<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/ResourceAbsenceController';
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
import type { Resource, ResourceAbsence } from '@/types/models';

interface Props {
    open: boolean;
    resourceAbsence?: ResourceAbsence | null;
    resources: Pick<Resource, 'id' | 'name'>[];
}

const props = withDefaults(defineProps<Props>(), {
    resourceAbsence: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.resourceAbsence !== null;

function formatDateTimeForInput(dateString: string): string {
    const match = dateString.match(
        /^(\d{4}-\d{2}-\d{2})(?:[T ](\d{2}):(\d{2}))?/,
    );

    if (!match) {
        return '';
    }

    const [, date, hour, minute] = match;

    return `${date}T${hour ?? '00'}:${minute ?? '00'}`;
}

const form = useForm({
    resource_id: null as number | null,
    starts_at: '',
    ends_at: '',
    recurrence_rule: '',
});

watch(
    () => props.open,
    (open) => {
        if (open && props.resourceAbsence) {
            form.resource_id = props.resourceAbsence.resource_id;
            form.starts_at = formatDateTimeForInput(
                props.resourceAbsence.starts_at,
            );
            form.ends_at = formatDateTimeForInput(
                props.resourceAbsence.ends_at,
            );
            form.recurrence_rule = props.resourceAbsence.recurrence_rule ?? '';
        } else if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    const action = isEditing() ? update(props.resourceAbsence!.id) : store();
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
        :title="
            isEditing() ? 'Abwesenheit bearbeiten' : 'Abwesenheit erstellen'
        "
        :description="
            isEditing()
                ? 'Ändern Sie die Abwesenheit.'
                : 'Erfassen Sie eine neue Abwesenheit.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="absence-resource"
                >Ressource <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.resource_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.resource_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="absence-resource">
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
                <Label for="absence-starts-at"
                    >Beginn <span class="text-destructive">*</span></Label
                >
                <Input
                    id="absence-starts-at"
                    v-model="form.starts_at"
                    type="datetime-local"
                    :disabled="form.processing"
                    required
                />
                <InputError :message="form.errors.starts_at" />
            </div>

            <div class="grid gap-2">
                <Label for="absence-ends-at"
                    >Ende <span class="text-destructive">*</span></Label
                >
                <Input
                    id="absence-ends-at"
                    v-model="form.ends_at"
                    type="datetime-local"
                    :disabled="form.processing"
                    required
                />
                <InputError :message="form.errors.ends_at" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="absence-recurrence">Wiederholungsregel</Label>
            <Input
                id="absence-recurrence"
                v-model="form.recurrence_rule"
                placeholder="Leer für einmalig"
                :disabled="form.processing"
            />
            <InputError :message="form.errors.recurrence_rule" />
        </div>
    </FormDialog>
</template>
