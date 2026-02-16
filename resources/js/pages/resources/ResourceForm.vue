<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/ResourceController';
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
import type { User } from '@/types';
import type { Resource, ResourceType } from '@/types/models';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    open: boolean;
    resource?: Resource | null;
    resourceTypes: Pick<ResourceType, 'id' | 'name'>[];
    users: Pick<User, 'id' | 'name'>[];
    capacityUnits: EnumOption[];
}

const props = withDefaults(defineProps<Props>(), {
    resource: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.resource !== null;

const form = useForm({
    name: '',
    resource_type_id: null as number | null,
    capacity_value: '' as string | number,
    capacity_unit: '',
    user_id: null as number | null,
});

watch(
    () => props.open,
    (open) => {
        if (open && props.resource) {
            form.name = props.resource.name;
            form.resource_type_id = props.resource.resource_type_id;
            form.capacity_value = props.resource.capacity_value ?? '';
            form.capacity_unit = props.resource.capacity_unit ?? '';
            form.user_id = props.resource.user_id;
        } else if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    const action = isEditing() ? update(props.resource!.id) : store();
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
        :title="isEditing() ? 'Ressource bearbeiten' : 'Ressource erstellen'"
        :description="
            isEditing()
                ? 'Ändern Sie die Eigenschaften dieser Ressource.'
                : 'Erstellen Sie eine neue Ressource.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="resource-name"
                >Name <span class="text-destructive">*</span></Label
            >
            <Input
                id="resource-name"
                v-model="form.name"
                placeholder="Name der Ressource"
                :disabled="form.processing"
                required
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="resource-type"
                >Ressourcentyp <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.resource_type_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.resource_type_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="resource-type">
                    <SelectValue placeholder="Ressourcentyp wählen" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="rt in resourceTypes"
                        :key="rt.id"
                        :value="rt.id.toString()"
                    >
                        {{ rt.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.resource_type_id" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="resource-capacity-value">Kapazität</Label>
                <Input
                    id="resource-capacity-value"
                    v-model="form.capacity_value"
                    type="number"
                    min="0"
                    step="0.01"
                    :placeholder="
                        form.capacity_unit === 'hours_per_day'
                            ? 'z.B. 8'
                            : 'z.B. 3'
                    "
                    :disabled="form.processing"
                />
                <InputError :message="form.errors.capacity_value" />
            </div>

            <div class="grid gap-2">
                <Label for="resource-capacity-unit">Einheit</Label>
                <Select
                    :model-value="form.capacity_unit ?? ''"
                    :disabled="form.processing"
                    @update:model-value="form.capacity_unit = $event || ''"
                >
                    <SelectTrigger id="resource-capacity-unit">
                        <SelectValue placeholder="Einheit wählen" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="u in capacityUnits"
                            :key="u.value"
                            :value="u.value"
                        >
                            {{ u.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.capacity_unit" />
            </div>
        </div>
        <p class="-mt-1 text-xs text-muted-foreground">
            <span v-if="form.capacity_unit === 'hours_per_day'">
                Wie viele Stunden pro Tag steht diese Ressource zur Verfügung?
                Beispiel: Ein Vollzeit-Mitarbeiter hat 8 Stunden/Tag.
            </span>
            <span v-else-if="form.capacity_unit === 'slots'">
                Wie viele parallele Slots stehen zur Verfügung? Beispiel: Ein
                Besprechungsraum hat 1 Slot, drei 3D-Drucker ergeben 3 Slots.
            </span>
            <span v-else>
                Wählen Sie eine Einheit, um festzulegen, wie die Verfügbarkeit
                dieser Ressource gemessen wird.
            </span>
        </p>

        <div class="grid gap-2">
            <Label for="resource-user">Benutzer</Label>
            <Select
                :model-value="form.user_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.user_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="resource-user">
                    <SelectValue placeholder="Kein Benutzer zugeordnet" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="u in users"
                        :key="u.id"
                        :value="u.id.toString()"
                    >
                        {{ u.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.user_id" />
        </div>
    </FormDialog>
</template>
