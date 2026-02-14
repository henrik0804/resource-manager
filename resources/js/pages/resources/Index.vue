<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/ResourceController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/resources';
import type { BreadcrumbItem, User } from '@/types';
import type { Paginated, Resource, ResourceType } from '@/types/models';

import ResourceForm from './ResourceForm.vue';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    resources: Paginated<Resource>;
    resourceTypes: Pick<ResourceType, 'id' | 'name'>[];
    users: Pick<User, 'id' | 'name'>[];
    capacityUnits: EnumOption[];
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ressourcen', href: index().url },
];

const columns: Column<Resource>[] = [
    { key: 'name', label: 'Name' },
    {
        key: 'resource_type',
        label: 'Typ',
        render: (row) => row.resource_type?.name ?? '—',
    },
    {
        key: 'capacity',
        label: 'Kapazität',
        render: (row) =>
            row.capacity_value
                ? `${row.capacity_value} ${row.capacity_unit ?? ''}`
                : '—',
    },
    {
        key: 'user',
        label: 'Benutzer',
        render: (row) => row.user?.name ?? '—',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}

const formOpen = ref(false);
const editingResource = ref<Resource | null>(null);

function openCreate() {
    editingResource.value = null;
    formOpen.value = true;
}

function openEdit(resource: Resource) {
    editingResource.value = resource;
    formOpen.value = true;
}
</script>

<template>
    <Head title="Ressourcen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Ressourcen"
                description="Verwalten Sie alle verfügbaren Ressourcen."
            />

            <DataTable
                :data="resources"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/resources"
                search-placeholder="Ressourcen suchen..."
                empty-message="Keine Ressourcen gefunden."
                delete-title="Ressource löschen"
                delete-description="Möchten Sie diese Ressource wirklich löschen?"
                dependency-delete-description="Diese Ressource hat abhängige Daten, die unwiderruflich mitgelöscht werden."
                @edit="openEdit"
            >
                <template #toolbar>
                    <Button @click="openCreate">
                        <Plus class="mr-2 size-4" />
                        Ressource erstellen
                    </Button>
                </template>
            </DataTable>
        </div>

        <ResourceForm
            :open="formOpen"
            :resource="editingResource"
            :resource-types="resourceTypes"
            :users="users"
            :capacity-units="capacityUnits"
            @update:open="formOpen = $event"
        />
    </AppLayout>
</template>
