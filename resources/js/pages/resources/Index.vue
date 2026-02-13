<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { destroy } from '@/actions/App/Http/Controllers/ResourceController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/resources';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, Resource } from '@/types/models';

interface Props {
    resources: Paginated<Resource>;
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
                delete-description="Möchten Sie diese Ressource wirklich löschen? Alle zugehörigen Abwesenheiten, Qualifikationen und Zuweisungen werden ebenfalls entfernt."
            />
        </div>
    </AppLayout>
</template>
