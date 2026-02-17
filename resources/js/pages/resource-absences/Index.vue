<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/ResourceAbsenceController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/resource-absences';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, Resource, ResourceAbsence } from '@/types/models';

import ResourceAbsenceForm from './ResourceAbsenceForm.vue';

interface Props {
    resourceAbsences: Paginated<ResourceAbsence>;
    resources: Pick<Resource, 'id' | 'name'>[];
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Abwesenheiten', href: index().url },
];

function formatDate(dateString: string): string {
    const normalized = dateString.includes('T')
        ? dateString
        : dateString.replace(' ', 'T');
    const parsed = new Date(normalized);

    if (Number.isNaN(parsed.getTime())) {
        return '—';
    }

    return parsed.toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
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

const formOpen = ref(false);
const editingResourceAbsence = ref<ResourceAbsence | null>(null);

function openCreate() {
    editingResourceAbsence.value = null;
    formOpen.value = true;
}

function openEdit(resourceAbsence: ResourceAbsence) {
    editingResourceAbsence.value = resourceAbsence;
    formOpen.value = true;
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
                @edit="openEdit"
            >
                <template #toolbar>
                    <Button @click="openCreate">
                        <Plus class="mr-2 size-4" />
                        Abwesenheit erstellen
                    </Button>
                </template>
            </DataTable>
        </div>

        <ResourceAbsenceForm
            :open="formOpen"
            :resource-absence="editingResourceAbsence"
            :resources="resources"
            @update:open="formOpen = $event"
        />
    </AppLayout>
</template>
