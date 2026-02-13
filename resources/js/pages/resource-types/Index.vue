<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { destroy } from '@/actions/App/Http/Controllers/ResourceTypeController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/resource-types';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, ResourceType } from '@/types/models';

interface Props {
    resourceTypes: Paginated<ResourceType>;
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ressourcentypen', href: index().url },
];

const columns: Column<ResourceType>[] = [
    { key: 'name', label: 'Name' },
    { key: 'description', label: 'Beschreibung' },
    {
        key: 'resources_count',
        label: 'Ressourcen',
        render: (row) => String(row.resources_count ?? 0),
        class: 'w-32',
    },
    {
        key: 'qualifications_count',
        label: 'Qualifikationen',
        render: (row) => String(row.qualifications_count ?? 0),
        class: 'w-40',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}
</script>

<template>
    <Head title="Ressourcentypen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Ressourcentypen"
                description="Verwalten Sie die verschiedenen Typen von Ressourcen."
            />

            <DataTable
                :data="resourceTypes"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/resource-types"
                search-placeholder="Ressourcentypen suchen..."
                empty-message="Keine Ressourcentypen gefunden."
                delete-title="Ressourcentyp löschen"
                delete-description="Möchten Sie diesen Ressourcentyp wirklich löschen? Alle zugehörigen Ressourcen und Qualifikationen werden ebenfalls entfernt."
            />
        </div>
    </AppLayout>
</template>
