<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus, Sparkles } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AutoAssign from '@/actions/App/Http/Controllers/AutoAssignController';
import { destroy } from '@/actions/App/Http/Controllers/TaskAssignmentController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { AccessSections } from '@/lib/access-sections';
import { index } from '@/routes/task-assignments';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import type {
    AutoAssignResponse,
    Paginated,
    Resource,
    Task,
    TaskAssignment,
} from '@/types/models';

import AutoAssignResultDialog from './AutoAssignResultDialog.vue';
import TaskAssignmentForm from './TaskAssignmentForm.vue';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    taskAssignments: Paginated<TaskAssignment>;
    tasks: Pick<Task, 'id' | 'title'>[];
    resources: Pick<
        Resource,
        'id' | 'name' | 'capacity_unit' | 'capacity_value'
    >[];
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

const canAutoAssign = computed(() => {
    const permissions = page.props.auth?.permissions ?? {};
    const permission = permissions[AccessSections.AutomatedAssignment];

    return permission?.can_write ?? false;
});

const autoAssignDialogOpen = ref(false);
const isAutoAssigning = ref(false);
const autoAssignResult = ref<AutoAssignResponse | null>(null);
const autoAssignResultOpen = ref(false);

function confirmAutoAssign() {
    autoAssignDialogOpen.value = true;
}

async function executeAutoAssign() {
    isAutoAssigning.value = true;

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

        const response = await fetch(AutoAssign.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        if (response.ok) {
            autoAssignResult.value =
                (await response.json()) as AutoAssignResponse;
            autoAssignResultOpen.value = true;
        }
    } finally {
        isAutoAssigning.value = false;
        autoAssignDialogOpen.value = false;
    }
}

function closeAutoAssignResult(open: boolean) {
    autoAssignResultOpen.value = open;

    if (!open) {
        router.reload();
    }
}

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
                <template v-if="canManageAssignments || canAutoAssign" #toolbar>
                    <div class="flex items-center gap-2">
                        <Button
                            v-if="canAutoAssign"
                            variant="secondary"
                            @click="confirmAutoAssign"
                        >
                            <Sparkles class="mr-2 size-4" />
                            Auto-Zuweisung
                        </Button>
                        <Button v-if="canManageAssignments" @click="openCreate">
                            <Plus class="mr-2 size-4" />
                            Zuweisung erstellen
                        </Button>
                    </div>
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

        <ConfirmDialog
            :open="autoAssignDialogOpen"
            title="Automatische Zuweisung"
            description="Nicht zugewiesene Aufgaben werden automatisch an verfügbare, qualifizierte Ressourcen mit der geringsten Auslastung zugewiesen. Fortfahren?"
            confirm-label="Zuweisen"
            variant="default"
            :processing="isAutoAssigning"
            @update:open="autoAssignDialogOpen = $event"
            @confirm="executeAutoAssign"
        />

        <AutoAssignResultDialog
            :open="autoAssignResultOpen"
            :result="autoAssignResult"
            @update:open="closeAutoAssignResult"
        />
    </AppLayout>
</template>
