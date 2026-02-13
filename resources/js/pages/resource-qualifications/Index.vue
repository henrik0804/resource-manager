<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { destroy } from '@/actions/App/Http/Controllers/ResourceQualificationController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/resource-qualifications';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, ResourceQualification } from '@/types/models';

const qualificationLevelLabels: Record<string, string> = {
    beginner: 'Anfänger',
    intermediate: 'Fortgeschritten',
    advanced: 'Erfahren',
    expert: 'Experte',
};

interface Props {
    resourceQualifications: Paginated<ResourceQualification>;
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ressourcenqualifikationen', href: index().url },
];

const columns: Column<ResourceQualification>[] = [
    {
        key: 'resource',
        label: 'Ressource',
        render: (row) => row.resource?.name ?? '—',
    },
    {
        key: 'qualification',
        label: 'Qualifikation',
        render: (row) => row.qualification?.name ?? '—',
    },
    {
        key: 'level',
        label: 'Stufe',
        render: (row) =>
            row.level
                ? (qualificationLevelLabels[row.level] ?? row.level)
                : '—',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}
</script>

<template>
    <Head title="Ressourcenqualifikationen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Ressourcenqualifikationen"
                description="Verwalten Sie die Qualifikationen, die Ressourcen zugeordnet sind."
            />

            <DataTable
                :data="resourceQualifications"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/resource-qualifications"
                search-placeholder="Ressourcenqualifikationen suchen..."
                empty-message="Keine Ressourcenqualifikationen gefunden."
                delete-title="Ressourcenqualifikation löschen"
                delete-description="Möchten Sie diese Ressourcenqualifikation wirklich löschen?"
            />
        </div>
    </AppLayout>
</template>
