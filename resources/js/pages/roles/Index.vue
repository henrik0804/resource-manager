<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { destroy } from '@/actions/App/Http/Controllers/RoleController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, Role } from '@/types/models';

interface Props {
    roles: Paginated<Role>;
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Rollen', href: index().url }];

const columns: Column<Role>[] = [
    { key: 'name', label: 'Name' },
    { key: 'description', label: 'Beschreibung' },
    {
        key: 'users_count',
        label: 'Benutzer',
        render: (row) => String(row.users_count ?? 0),
        class: 'w-32',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}
</script>

<template>
    <Head title="Rollen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Rollen"
                description="Verwalten Sie die Benutzerrollen im System."
            />

            <DataTable
                :data="roles"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/roles"
                search-placeholder="Rollen suchen..."
                empty-message="Keine Rollen gefunden."
                delete-title="Rolle löschen"
                delete-description="Möchten Sie diese Rolle wirklich löschen? Benutzer mit dieser Rolle werden davon betroffen."
            />
        </div>
    </AppLayout>
</template>
