<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { destroy } from '@/actions/App/Http/Controllers/TaskRequirementController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/task-requirements';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, TaskRequirement } from '@/types/models';

const qualificationLevelLabels: Record<string, string> = {
    beginner: 'Anfänger',
    intermediate: 'Fortgeschritten',
    advanced: 'Erfahren',
    expert: 'Experte',
};

interface Props {
    taskRequirements: Paginated<TaskRequirement>;
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Aufgabenanforderungen', href: index().url },
];

const columns: Column<TaskRequirement>[] = [
    {
        key: 'task',
        label: 'Aufgabe',
        render: (row) => row.task?.title ?? '—',
    },
    {
        key: 'qualification',
        label: 'Qualifikation',
        render: (row) => row.qualification?.name ?? '—',
    },
    {
        key: 'required_level',
        label: 'Benötigte Stufe',
        render: (row) =>
            row.required_level
                ? (qualificationLevelLabels[row.required_level] ??
                  row.required_level)
                : '—',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}
</script>

<template>
    <Head title="Aufgabenanforderungen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Aufgabenanforderungen"
                description="Verwalten Sie die Qualifikationsanforderungen für Aufgaben."
            />

            <DataTable
                :data="taskRequirements"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/task-requirements"
                search-placeholder="Aufgabenanforderungen suchen..."
                empty-message="Keine Aufgabenanforderungen gefunden."
                delete-title="Aufgabenanforderung löschen"
                delete-description="Möchten Sie diese Aufgabenanforderung wirklich löschen?"
            />
        </div>
    </AppLayout>
</template>
