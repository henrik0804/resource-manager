<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/TaskAssignmentController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { AccessSections } from '@/lib/access-sections';
import { index } from '@/routes/task-assignments';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import type { Paginated, Resource, Task, TaskAssignment } from '@/types/models';

import TaskAssignmentForm from './TaskAssignmentForm.vue';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    taskAssignments: Paginated<TaskAssignment>;
    tasks: Pick<Task, 'id' | 'title'>[];
    resources: Pick<Resource, 'id' | 'name'>[];
    assignmentSources: EnumOption[];
    assigneeStatuses: EnumOption[];
    search: string;
}

defineProps<Props>();

const page = usePage<AppPageProps>();

const canManageAssignments = computed(() => {
    const permissions = page.props.auth?.permissions ?? {};
    const permission = permissions[AccessSections.ManualAssignment];

    return permission?.can_write ?? false;
});

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

const formOpen = ref(false);
const editingTaskAssignment = ref<TaskAssignment | null>(null);

function openCreate() {
    editingTaskAssignment.value = null;
    formOpen.value = true;
}

function openEdit(taskAssignment: TaskAssignment) {
    editingTaskAssignment.value = taskAssignment;
    formOpen.value = true;
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
                :show-actions="canManageAssignments"
                route-prefix="/task-assignments"
                search-placeholder="Aufgabenzuweisungen suchen..."
                empty-message="Keine Aufgabenzuweisungen gefunden."
                delete-title="Aufgabenzuweisung löschen"
                delete-description="Möchten Sie diese Aufgabenzuweisung wirklich löschen?"
                @edit="openEdit"
            >
                <template v-if="canManageAssignments" #toolbar>
                    <Button @click="openCreate">
                        <Plus class="mr-2 size-4" />
                        Zuweisung erstellen
                    </Button>
                </template>
            </DataTable>
        </div>

        <TaskAssignmentForm
            :open="formOpen"
            :task-assignment="editingTaskAssignment"
            :tasks="tasks"
            :resources="resources"
            :assignment-sources="assignmentSources"
            :assignee-statuses="assigneeStatuses"
            @update:open="formOpen = $event"
        />
    </AppLayout>
</template>
