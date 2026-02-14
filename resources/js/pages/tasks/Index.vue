<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/TaskController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/tasks';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, Task } from '@/types/models';

import TaskForm from './TaskForm.vue';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    tasks: Paginated<Task>;
    priorities: EnumOption[];
    statuses: EnumOption[];
    effortUnits: EnumOption[];
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

const formOpen = ref(false);
const editingTask = ref<Task | null>(null);

function openCreate() {
    editingTask.value = null;
    formOpen.value = true;
}

function openEdit(task: Task) {
    editingTask.value = task;
    formOpen.value = true;
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
                delete-description="Möchten Sie diese Aufgabe wirklich löschen?"
                dependency-delete-description="Diese Aufgabe hat abhängige Anforderungen und Zuweisungen, die unwiderruflich mitgelöscht werden."
                @edit="openEdit"
            >
                <template #toolbar>
                    <Button @click="openCreate">
                        <Plus class="mr-2 size-4" />
                        Aufgabe erstellen
                    </Button>
                </template>
            </DataTable>
        </div>

        <TaskForm
            :open="formOpen"
            :task="editingTask"
            :priorities="priorities"
            :statuses="statuses"
            :effort-units="effortUnits"
            @update:open="formOpen = $event"
        />
    </AppLayout>
</template>
