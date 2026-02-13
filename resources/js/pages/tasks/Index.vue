<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { destroy } from '@/actions/App/Http/Controllers/TaskController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/tasks';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, Task } from '@/types/models';

interface Props {
    tasks: Paginated<Task>;
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Aufgaben', href: index().url },
];

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

const columns: Column<Task>[] = [
    { key: 'title', label: 'Titel' },
    {
        key: 'status',
        label: 'Status',
        class: 'w-28',
    },
    {
        key: 'priority',
        label: 'Priorität',
        class: 'w-28',
    },
    {
        key: 'starts_at',
        label: 'Beginn',
        render: (row) => formatDate(row.starts_at),
        class: 'w-28',
    },
    {
        key: 'ends_at',
        label: 'Ende',
        render: (row) => formatDate(row.ends_at),
        class: 'w-28',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}
</script>

<template>
    <Head title="Aufgaben" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Aufgaben"
                description="Verwalten Sie alle Aufgaben und deren Zuweisung."
            />

            <DataTable
                :data="tasks"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/tasks"
                search-placeholder="Aufgaben suchen..."
                empty-message="Keine Aufgaben gefunden."
                delete-title="Aufgabe löschen"
                delete-description="Möchten Sie diese Aufgabe wirklich löschen? Alle zugehörigen Anforderungen und Zuweisungen werden ebenfalls entfernt."
            />
        </div>
    </AppLayout>
</template>
