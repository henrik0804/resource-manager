<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { destroy } from '@/actions/App/Http/Controllers/TaskAssignmentController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/task-assignments';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, TaskAssignment } from '@/types/models';

interface Props {
    taskAssignments: Paginated<TaskAssignment>;
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Aufgabenzuweisungen', href: index().url },
];

function formatDate(dateString: string | null): string {
    if (!dateString) {
        return '—';
    }

    return new Date(dateString).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

const columns: Column<TaskAssignment>[] = [
    {
        key: 'task',
        label: 'Aufgabe',
        render: (row) => row.task?.title ?? '—',
    },
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
        key: 'assignment_source',
        label: 'Quelle',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}
</script>

<template>
    <Head title="Aufgabenzuweisungen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Aufgabenzuweisungen"
                description="Verwalten Sie die Zuweisungen von Ressourcen zu Aufgaben."
            />

            <DataTable
                :data="taskAssignments"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/task-assignments"
                search-placeholder="Aufgabenzuweisungen suchen..."
                empty-message="Keine Aufgabenzuweisungen gefunden."
                delete-title="Aufgabenzuweisung löschen"
                delete-description="Möchten Sie diese Aufgabenzuweisung wirklich löschen?"
            />
        </div>
    </AppLayout>
</template>
