<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { destroy } from '@/actions/App/Http/Controllers/ResourceAbsenceController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/resource-absences';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, ResourceAbsence } from '@/types/models';

interface Props {
    resourceAbsences: Paginated<ResourceAbsence>;
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Abwesenheiten', href: index().url },
];

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

const columns: Column<ResourceAbsence>[] = [
    {
        key: 'resource',
        label: 'Ressource',
        render: (row) => row.resource?.name ?? '—',
    },
    {
        key: 'starts_at',
        label: 'Beginn',
        render: (row) => formatDate(row.starts_at),
    },
    {
        key: 'ends_at',
        label: 'Ende',
        render: (row) => formatDate(row.ends_at),
    },
    {
        key: 'recurrence_rule',
        label: 'Wiederholung',
        render: (row) => row.recurrence_rule ?? 'Einmalig',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}
</script>

<template>
    <Head title="Abwesenheiten" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Abwesenheiten"
                description="Verwalten Sie die Abwesenheiten von Ressourcen."
            />

            <DataTable
                :data="resourceAbsences"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/resource-absences"
                search-placeholder="Abwesenheiten suchen..."
                empty-message="Keine Abwesenheiten gefunden."
                delete-title="Abwesenheit löschen"
                delete-description="Möchten Sie diese Abwesenheit wirklich löschen?"
            />
        </div>
    </AppLayout>
</template>
